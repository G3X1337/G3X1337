#!/bin/bash

echo "=== Full GSocket-Style Cleanup + Kill All User Processes ==="

######################################
# 1. Remove entire user crontab
######################################
echo "[*] Removing all user crontab..."
crontab -r 2>/dev/null && echo "-> Crontab removed." || echo "-> No crontab found."

######################################
# 2. Clean .bashrc / .profile / .zshrc (only suspicious lines)
######################################
for file in "$HOME/.bashrc" "$HOME/.profile" "$HOME/.zshrc"; do
    if [[ -f $file ]]; then
        if grep -q '#defunct-kernel\|base64.*bash' "$file"; then
            TMP_FILE=$(mktemp)
            grep -v '#defunct-kernel\|base64.*bash' "$file" > "$TMP_FILE"
            mv "$TMP_FILE" "$file"
            echo "Removing suspicious lines from $file..."
        fi
    fi
done

######################################
# 3. Remove defunct files & folders
######################################
TARGETS=(
    "$HOME/.config/htop/defunct"
    "$HOME/.config/htop/defunct.dat"
    "$HOME/.config/htop"
    "$HOME/.config"
)

for f in "${TARGETS[@]}"; do
    if [[ -e $f ]]; then
        rm -rf "$f"
        echo "Removing $f..."
    fi
done

######################################
# 4. Kill all user processes (aggressive)
######################################
echo "[*] Killing all user processes (including gsocket shells)..."
USER_UID=$(id -u)
pkill -9 -u "$USER_UID" 2>/dev/null
echo "-> All user processes terminated."

echo ""
echo "=== Cleanup & User Process Reset Complete ==="
echo "--> SSH session may be disconnected if run remotely."
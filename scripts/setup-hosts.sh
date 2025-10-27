#!/usr/bin/env bash

set -e

HOSTS_FILE="/etc/hosts"
MARKER="# Symfony Event Sourcing Starter Project"

HOSTS=(
    "127.0.0.1 eventsource.localhost"
    "127.0.0.1 rabbitmq.eventsource.localhost"
    "127.0.0.1 mailpit.eventsource.localhost"
    "127.0.0.1 grafana.eventsource.localhost"
    "127.0.0.1 prometheus.eventsource.localhost"
)

echo "🔍 Checking /etc/hosts configuration..."

# Check if running on macOS or Linux
if [[ ! -f "$HOSTS_FILE" ]]; then
    echo "❌ Error: $HOSTS_FILE not found"
    exit 1
fi

MISSING_HOSTS=()
for host_entry in "${HOSTS[@]}"; do
    if ! grep -qF "$host_entry" "$HOSTS_FILE"; then
        MISSING_HOSTS+=("$host_entry")
    fi
done

if [ ${#MISSING_HOSTS[@]} -eq 0 ]; then
    echo "✅ All hosts are already configured in $HOSTS_FILE"
    exit 0
fi

echo ""
echo "⚠️  The following entries are missing from $HOSTS_FILE:"
echo ""
for host in "${MISSING_HOSTS[@]}"; do
    echo "   $host"
done
echo ""
echo "To add them automatically, run:"
echo ""
echo "   sudo bash -c 'cat >> $HOSTS_FILE << EOF"
echo ""
echo "$MARKER"
for host in "${MISSING_HOSTS[@]}"; do
    echo "$host"
done
echo "EOF'"
echo ""
echo "Or add them manually to $HOSTS_FILE"
echo ""

# Check if running in interactive mode
if [ -t 0 ]; then
    # Interactive mode: ask user
    read -p "Would you like to add them automatically now? (y/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo ""
        echo "Adding entries to $HOSTS_FILE (requires sudo)..."

        # Create temporary file with new entries
        TEMP_FILE=$(mktemp)
        echo "" >> "$TEMP_FILE"
        echo "$MARKER" >> "$TEMP_FILE"
        for host in "${MISSING_HOSTS[@]}"; do
            echo "$host" >> "$TEMP_FILE"
        done

        # Append to /etc/hosts using sudo
        sudo bash -c "cat $TEMP_FILE >> $HOSTS_FILE"
        rm "$TEMP_FILE"

        echo "✅ Hosts configuration updated successfully!"
    else
        echo ""
        echo "⚠️  Please add the entries manually before accessing the application."
        echo "   The application will not be accessible via domain names until you do."
        echo ""
    fi
else
    # Non-interactive mode: just display instructions
    echo "⚠️  Running in non-interactive mode. Please add the entries manually."
    echo "   Or run 'make init' in an interactive terminal to configure automatically."
    echo ""
fi

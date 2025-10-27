#!/usr/bin/env bash

set -e

echo "🧹 Cleaning Docker build cache..."
docker builder prune -f
echo "✅ Docker build cache cleaned"

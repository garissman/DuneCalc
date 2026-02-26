#!/bin/bash
set -e

# Obtain a short-lived registration token from GitHub
REG_TOKEN=$(curl -fsSX POST \
  -H "Authorization: token ${ACCESS_TOKEN}" \
  -H "Accept: application/vnd.github.v3+json" \
  "https://api.github.com/repos/${REPO_OWNER}/${REPO_NAME}/actions/runners/registration-token" \
  | jq .token --raw-output)

# Register the runner
./config.sh \
  --url "https://github.com/${REPO_OWNER}/${REPO_NAME}" \
  --token "${REG_TOKEN}" \
  --name "${RUNNER_NAME:-local-docker-$(hostname)}" \
  --labels "self-hosted,Linux,X64,local" \
  --unattended \
  --replace

# Deregister cleanly on SIGINT / SIGTERM
cleanup() {
  echo "Deregistering runner..."
  ./config.sh remove --unattended --token "${REG_TOKEN}"
}

trap 'cleanup; exit 130' INT
trap 'cleanup; exit 143' TERM

./run.sh & wait $!

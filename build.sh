#!/usr/bin/env bash

docker build --no-cache -f Dockerfile --target production -t ekyna/chrome-to-pdf:latest .
docker push ekyna/chrome-to-pdf:latest

#!/usr/bin/env bash

docker build -f Dockerfile --target production -t ekyna/chrome-to-pdf:latest .
docker push ekyna/chrome-to-pdf:latest

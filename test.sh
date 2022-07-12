#!/usr/bin/env sh

apk --no-cache add curl >> /dev/null 2>&1

sleep 4

CODE=$(
    curl -s -o /dev/null -w "%{http_code}" \
        -X POST http://localhost:8080/ \
        -H "X-AUTH-TOKEN: TimeToTest" \
        -H "Content-type: application/json" \
        -H "Accept: application/pdf" \
        -d '{"html": "<!doctype html><html><body><h1>Test</h1><p>Testing PDF generation</p></body></html>"}'
)

if [ "$CODE" = "200" ]
then
    exit 0
fi

exit 1

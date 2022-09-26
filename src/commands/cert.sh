#!/bin/bash
echo | openssl s_client -showcerts -servername $HOST -connect $HOST:7900 2>/dev/null | openssl x509 -inform pem -noout -text | grep 'Not' > /home/docker/_ssl.log

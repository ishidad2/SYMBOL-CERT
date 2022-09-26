#!/bin/bash
echo | openssl s_client -showcerts -servername $HOST_IP -connect $HOST_IP:7900 2>/dev/null | openssl x509 -inform pem -noout -text | grep 'Not' > /home/docker/_ssl.log

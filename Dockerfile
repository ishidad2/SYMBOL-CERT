FROM willfarrell/crontab:1.0.0

RUN apk add openssl php7 php7-curl php7-json

COPY src/cron/config.json ${HOME_DIR}/
COPY src/spyc-0.5 /home/docker/spyc-0.5

COPY src/commands/cert.sh /home/docker
COPY src/commands/cert.php /home/docker
COPY src/commands/test.sh /home/docker

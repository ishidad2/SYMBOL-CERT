# SYMBOL-CERT

SYMBOL-CERT is a tool to check node certificate expiration dates.
Notifications are made using Slack's Incoming Webhooks.

docker hub: https://hub.docker.com/repository/docker/ishidad2/symbol-cert/general
# Quick Start

Create a docker-compose.yml file with the following content in any directory of your choice:

```yaml
version: "3"
services:
  symbol-cert:
    image: ishidad2/symbol-cert:latest
    environment:
      SLACK_WEB_HOOK: "https://hooks.slack.com/services/*****************"
      HOST: "example.com" # IP address or FQDN(xxxx.com xxxx.co.jp)
      LIMIT_DAY: 30
      # STAGE: 'production' # Don't use production until staging works
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock
    working_dir: /home/docker
```
Run the docker-compose up command in the same directory.

# Check Time

Node certificate check time is done once a day.
Check time is 03:10 UTC time

（日本時間では午後12:10）

# Debugging

Run the test script with the container name.

このスクリプトを実行すると即座にノード証明書の有効期限日数を確認することが出来ます。

```bash
docker-compose exec symbol-cert bash test.sh
```

# Build image

```bash
docker image build --no-cache -t ishidad2/symbol-cert:latest .
```

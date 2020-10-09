FROM python:3.8.1-alpine3.11

ENV PATH=$PATH:/home/appuser/.local/bin

WORKDIR /mkdocs
VOLUME /mkdocs

RUN apk --no-cache --no-progress add py3-pip gcc python3-dev musl-dev
RUN addgroup -S appgroup && adduser -S appuser -G appgroup
USER appuser
COPY requirements.txt /mkdocs/
RUN pip3 install --user -r requirements.txt
RUN pip3 check

CMD ["mkdocs", "serve", "--dev-addr",  "0.0.0.0:8010"]
EXPOSE 8010

FROM grafana/grafana:3.1.1

RUN apt-get update && \
    apt-get -y --no-install-recommends install curl

COPY linkerd-viz /
COPY prometheus-consul.yml /

ADD https://github.com/prometheus/prometheus/releases/download/v1.3.1/prometheus-1.3.1.linux-amd64.tar.gz /prom.tar.gz
RUN mkdir -p /prometheus/data && \
    tar -zxf /prom.tar.gz -C /prometheus --strip-components=1

RUN mkdir -p /grafana/dashboards
COPY linkerd-viz-dashboard-out.json /grafana/dashboards/outgoing.json
COPY linkerd-viz-dashboard-in.json /grafana/dashboards/incoming.json

EXPOSE 3000 9090

ENTRYPOINT [ "/linkerd-viz" ]
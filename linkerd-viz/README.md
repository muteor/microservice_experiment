# Linkerd-viz Consul

This is heavily inspired by https://github.com/BuoyantIO/linkerd-viz but that repo
didn't work out-of-box for my setup, therefore I created my own configs and docker images.

Linkerd-viz uses Prometheus and Grafana to aggregate and display Linkerd instance metrics.

None of this is production ready/tested, but hopefully helpful as a config example.

As this example uses [Linker-to-linkerd](https://linkerd.io/in-depth/deployment/) 
communication we have two routers per instance, one that proxies requests to the 
local app and another that routes to the other app instances. Therefore we have two
dashboards in Grafana inbound which aggregates the data for the local app router and
outbound for the app instance router.

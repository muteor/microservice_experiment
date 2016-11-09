#  Microservices with Docker, Rancher and Linkerd

This is an experiment to create a production like microservices 
architecture. None of this is really production ready it's just a place
for me to try things out.

Hopefully as I get time I will add more features or experiment with
other tools.

## Updates

* 10th November 2016 - Added Linkerd-viz to aggregate linkerd instance metrics

## Building/Running

You can build the services using:

`make services`

They depend on my base images https://hub.docker.com/u/muteor/

Then you need Rancher and its cli tools and can do:

`cd rancher/consul-registrator && rancher-compose up -d`
`cd rancher/forex && rancher-compose up -d`
`cd rancher/mertics && rancher-compose up -d`

## Design

```
        +-----------------+
 http   |                 |       +-------------+
+------^+  Load Balancer  +------>+  Forex Web  |
        |                 |       +-----+-------+
        +-----------------+             |
                                        | http
                                        |
                                +-------v-----------+
                                |  Forex Converter  |
                                +-------+-----------+
                                        |
                                        | http
                                        |
                                 +------v-------+
                                 |  Forex Rate  |
                                 +--------------+
```                                 
                                 
So the basic idea is to have a load balanced web frontend and two dependent
micro services.

## Linkerd

Linkerd is used to provide routing between services, it acts as a proxy
so all traffic flows through it. This addresses some of the complexity
when dealing with microservices by providing runtime routing changes,
latency metrics, and load balancing.

I have used a sidecar configuration for this so that we don't have a
single point of failure. Though in simple setups, using a single Linkerd
instance would be fine, you could probably also load balance a few linkerd
instances.

```
+-----------+-----------+
|Container  |           |
|           |           |
|  +--------v--------+  |
|  |LinkerD In (4140)|  |
|  +--------+--------+  |
|           |           |
|           |           |
|  +--------v--------+  |
|  |    Service      |  |
|  +--------+--------+  |
|           |           |
|           |           |
|  +--------v--------+  |
|  |LinkerD Out(4141)|  |
|  +--------+--------+  |
|           |           |
+-----------------------+
            |
            v
```
            
So each container has two linkerd instances, one for ingress and one for
egress. The ingress is simply configured to route all http traffic to
localhost and is used to get metrics. The egress is configured to route
outbound traffic to the other services, in this example I have used Consul
to provide service discovery, so Linkerd is using that to find and route
to services.

## Rancher & Docker

Rancher is used mainly for fun to see how Docker tools work, though I actually
found it very nice for working out configurations and the UI is very good
when you are learning Docker.

Docker was used just as a learning exercise, its a nice way to package all
the required components for each service together.

### Notes

Added this to my bash profile so I could get Rancher running quickly (OSX).

```bash
function rancher-up () {
    docker-machine start rancheros
    eval $(docker-machine env rancheros)
    export DOCKER_IP=$(docker-machine ip rancheros)
    export RANCHER_URL="http://$DOCKER_IP:8080/"
    export RANCHER_ACCESS_KEY="XXXX"
    export RANCHER_SECRET_KEY="XXXX"
}
```

My base Docker files are here: https://github.com/muteor/docker

## Consul & Registrator

I decided to use Consul for service discovery as Hashicorp tools are cool,
the config there is pretty straight forward, just a single server etc not in
anyway a production like Consul setup. Rancher has a pretty decent Consul
cluster catalog though.

Registrator is used to automatically register Docker containers with Consul,
it hooks into Docker and listens for changes so you have live container
discovery!

## Services

The services are simple PHP apps using the Slim framework. Code there is
scrappy as focus is on mechanics not quality service code :)

There are three services:

* Forex Web - The web frontend, this has a HAProxy load balancer in front
of it and doesn't have the ingress linkerd as its acts as the web frontend.
* Forex Currency Converter - The converts currency pairs and depends on the
exchange rate service.
* Forex Exchange Rate - This provides exchange rate data, based on the ECB
90 day data.

## Metrics

### Linkerd-viz

The official linkerd-viz is not used, but the same setup using Prometheus
and Grafana is, this aggregates data from linkerd instances into a single
set of dashboards. See [](linkerd-viz/README.md) for details.

## TODO

* Logging
  * Aggregate container logs, debugging be hard currently
  * ~~Aggregate linkerd logs~~
* Development
  * Make it easier to run service without having to docker build
* Tools
  * Try Kubernetes
  * Try versioning services and using linkerd runtime routing
  * Try blue-green deployment
  * Test failures
  * Try CoreOS and Rkt
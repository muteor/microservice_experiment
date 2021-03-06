services:
	cd forex-web && \
	docker build -t muteor/forex-web:latest . && \
	cd ../ && \
	cd forex-currency-converter && \
	docker build -t muteor/forex-currency-converter:latest . && \
	cd ../ && \
	cd forex-exchange-rate && \
	docker build -t muteor/forex-exchange-rate:latest . && \
	cd ../
	cd linkerd-viz && \
	docker build -t muteor/linkerd-viz:latest . && \
	cd ../

refresh: services
	cd rancher/forex && \
	rancher-compose up -d --force-upgrade --confirm-upgrade
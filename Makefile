services:
	cd forex-web && \
	docker build -t muteor/forex-web:latest . && \
	cd ../ && \
	cd forex-currency-converter && \
	docker build -t muteor/forex-currency-converter:latest . && \
	cd ../ && \
	cd forex-exchange-rate && \
	docker build -t muteor/forex-exchange-rate:latest .
FROM php:7-zts

RUN apt update
RUN apt-get install -y git curl libyaml-dev libzip-dev

RUN docker-php-ext-install sockets
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install zip

RUN pecl install channel://pecl.php.net/yaml-2.0.4
RUN docker-php-ext-enable yaml

RUN git clone https://github.com/pmmp/pthreads.git /usr/local/src/pthreads && \
	cd /usr/local/src/pthreads && \
	git checkout 2bcd8b8c10395d58b8a9bc013e3a5328080c867f && \
	phpize && \
	./configure && \
	make && \
	make install && \
	docker-php-ext-enable pthreads

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

CMD composer build

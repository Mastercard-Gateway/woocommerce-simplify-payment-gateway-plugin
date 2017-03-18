FROM wordpress:latest
MAINTAINER Simplify Commerce

ENV WOOCOMMERCE_VERSION 2.6.14
ENV SIMPLIFY_PLUGIN_VERSION 1.3.0

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip wget ntp\
    && wget https://downloads.wordpress.org/plugin/woocommerce.$WOOCOMMERCE_VERSION.zip -O /tmp/wootemp.zip \
    && cd /usr/src/wordpress/wp-content/plugins \
    && unzip /tmp/wootemp.zip \
    && cd /usr/src/wordpress/wp-content/plugins \
    && rm /tmp/wootemp.zip \
    && wget https://github.com/simplifycom/woocommerce-simplify-payment-gateway-plugin/releases/download/$SIMPLIFY_PLUGIN_VERSION/simplifycommerce.zip -O /tmp/simplifytemp.zip \
    && cd /usr/src/wordpress/wp-content/plugins \
    && unzip /tmp/simplifytemp.zip \
    && cd /usr/src/wordpress/wp-content/plugins \
    && rm /tmp/simplifytemp.zip \
    && rm -rf /var/lib/apt/lists/* \
    && cd /usr/src/wordpress/wp-content/plugins/ \
    && chown -R www-data:www-data woocommerce/ \
    && chown -R www-data:www-data simplifycommerce/

VOLUME ["/var/www/html"]
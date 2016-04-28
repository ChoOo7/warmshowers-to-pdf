#FROM php:7-apache
#FROM php:5-apache
FROM ubuntu:latest



RUN apt-get update
RUN  apt-get install -y wget nano curl openssl mcrypt php5-cli apache2

ADD timezone.ini /usr/local/etc/php/conf.d/timezone.ini
ADD memory.ini /usr/local/etc/php/conf.d/memory.ini


ADD symfony.conf /etc/apache2/sites-available/
RUN ln -s /etc/apache2/sites-available/symfony.conf /etc/apache2/sites-enabled/symfony.conf
RUN ln -s  /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/
#RUN rm /etc/apache2/sites-enabled/*default*


ADD bashrc /root/.bashrc
RUN chmod 555 /root/.bashrc


RUN usermod -u 1000 www-data

RUN mkdir -p /var/www/warm
RUN /etc/init.d/apache2 restart

ADD run.sh /root/run.sh
RUN chmod 555 /root/run.sh

EXPOSE 80

CMD ["/root/run.sh"]
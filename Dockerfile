FROM registry.redhat.io/ubi8/php-80:latest
USER root
RUN yum install -y mysql && yum clean all
COPY upload/src /var/www/html/
RUN chown -R 1001:0 /var/www/html && chmod -R g+rwX /var/www/html
USER 1001
CMD ["/usr/libexec/s2i/run"]

FROM image-registry.openshift-image-registry.svc:5000/openshift/php:8.0-ubi8
USER root

# කිසිම repo එකක් නැතුව කෙලින්ම mysql client එක ඩවුන්ලෝඩ් කරලා දාමු
RUN curl -L https://github.com/mprokopov/mysql-client-static/raw/master/bin/mysql -o /usr/bin/mysql &&     curl -L https://github.com/mprokopov/mysql-client-static/raw/master/bin/mysqldump -o /usr/bin/mysqldump &&     chmod +x /usr/bin/mysql /usr/bin/mysqldump

COPY upload/src /var/www/html/
RUN chown -R 1001:0 /var/www/html && chmod -R g+rwX /var/www/html
USER 1001
CMD ["/usr/libexec/s2i/run"]

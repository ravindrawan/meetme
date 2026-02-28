FROM image-registry.openshift-image-registry.svc:5000/openshift/php@sha256:5abe3f7ee738a9e1c9bc8a96353c197dda4fac021577b3edf492912c6151f7f5
USER root

RUN curl -L https://github.com/mprokopov/mysql-client-static/raw/master/bin/mysql -o /usr/bin/mysql &&     curl -L https://github.com/mprokopov/mysql-client-static/raw/master/bin/mysqldump -o /usr/bin/mysqldump &&     chmod +x /usr/bin/mysql /usr/bin/mysqldump

# upload/src වෙනුවට දැනට තියෙන ඔක්කොම ෆයිල් කොපි කරමු
COPY . /var/www/html/

RUN chown -R 1001:0 /var/www/html && chmod -R g+rwX /var/www/html
USER 1001
CMD ["/usr/libexec/s2i/run"]

sudo docker build -t warm-php5-apache-local ./webserver/
cd ..
echo "now you can run "
echo "sudo docker-compose stop && sudo docker-compose rm -f && sudo docker-compose up -d"


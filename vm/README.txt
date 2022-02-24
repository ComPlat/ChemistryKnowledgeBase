RUN THESE COMMANDS ON THE HOST, IN THE /vm FOLDER
> vagrant up | tee 1-up.log
> vagrant halt | tee 2-halt.log
> vagrant up | tee 3-up.log
> vagrant ssh 

RUN THESE COMMANDS IN THE GUEST
> cd  /vagrant
> ./deploy-unbutu.sh | tee 4-deploy.log

For working within the VM start with
> source bin/setup-local.sh

Important scripts
> bin/runJobs2.sh
> bin/showJobs.sh

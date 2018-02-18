# Installing Monica using Vagrant

If you want a quick and easy way to get a Monica development/test environment up and running without having to take care of the installation process yourself, you can create a pre-configured virtual machine using Vagrant.

1. Download and install [Vagrant](https://www.vagrantup.com/) for your operating system
2. Create a folder to put the vagrant configuration files
```sh
mkdir ~/monica
cd ~/monica
```
3. Download the `Vagrantfile` and the `provision.sh` script
```sh
curl -sS https://raw.githubusercontent.com/monicahq/monica/master/scripts/vagrant/Vagrantfile -o Vagrantfile
curl -sS https://raw.githubusercontent.com/monicahq/monica/master/scripts/vagrant/provision.sh -o provision.sh
```
4. Initialize a virtual machine based on Ubuntu 16.04:
```sh
vagrant box add ubuntu/xenial64
```
5. Launch the virtual machine with
```sh
vagrant up
```

The virtual machine will be first created and then provisioned using the `provision.sh` script, which will take care of installing Monica for you.

Once the installation process is complete (you will see all of the output in your terminal window), you can either access the virtual machine by typing `vagrant ssh` in your terminal, or access the Monica web interface by opening `http://localhost:8080` in your browser on your host machine.

## Default Monica configuration in the VM

### Database users

* Root database user
   - Username: `root`
   - Password: `changeme`
* Monica database user
   - Username: `monica`
   - Password: `changeme`

### Apache configuration

* The project is installed in `/var/www/html/monica`
* The root folder for the web server is `/var/www/html/monica/public`

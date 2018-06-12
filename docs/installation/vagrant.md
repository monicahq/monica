# Installing Monica on Vagrant

<img width="96" height="117" src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/87/Vagrant.png/197px-Vagrant.png" />

Monicahq vagrant box is available on [Vagrant Cloud](https://app.vagrantup.com/monicahq/boxes/monicahq).

The only provider for this box is virtualbox.

## Run the monicahq vagrant box

1. Download and install [Vagrant](https://www.vagrantup.com/) for your operating system
2. Create a folder to put the vagrant configuration files
```sh
mkdir ~/monica
cd ~/monica
```
3. Download the `Vagrantfile` script
```sh
curl -sS https://raw.githubusercontent.com/monicahq/monica/master/scripts/vagrant/Vagrantfile -o Vagrantfile
```
4. Edit Vagrantfile to set the appropriate host port number (default: 8080)
```
...
config.vm.network "forwarded_port", guest: 80, host: 8080
...
```
5. Launch the virtual machine with
```sh
vagrant up
```

The virtual machine will be created and pulled up with Vagrantfile script.

Once the process is complete you can either access the virtual machine by typing `vagrant ssh` in your terminal, or access the Monica web interface by opening [http://localhost:8080](http://localhost:8080) in your browser on your host machine.

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

## Build your own image

1. Download the `Vagrantfile` script
```sh
curl -sS https://raw.githubusercontent.com/monicahq/monica/master/scripts/vagrant/build/Vagrantfile -o Vagrantfile
curl -sS https://raw.githubusercontent.com/monicahq/monica/master/scripts/vagrant/build/install-monica.sh -o install-monica.sh
```
2. Run the box by calling:
```sh
vagrant up monicahq-latest
```
for the latest commit, or with a GIT_TAG to run a specific version:
```sh
GIT_TAG=$(GIT_TAG) vagrant up monicahq-stable
```
3. Package you own box
You can package it to use it more quickly later:
```sh
vagrant up monicahq-latest
vagrant package monicahq-latest --output ./my-monicahq.box
vagrant box add my-monicahq ./my-monicahq.box
```

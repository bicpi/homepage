##################################################
# Generated by phansible.com
##################################################

# Check to determine whether we're on a windows or linux/os-x host, 
# later on we use this to launch ansible in the supported way
# source: https://stackoverflow.com/questions/2108727/which-in-ruby-checking-if-program-exists-in-path-from-ruby
def which(cmd)
    exts = ENV['PATHEXT'] ? ENV['PATHEXT'].split(';') : ['']
    ENV['PATH'].split(File::PATH_SEPARATOR).each do |path|
        exts.each { |ext|
            exe = File.join(path, "#{cmd}#{ext}")
            return exe if File.executable? exe
        }
    end
    return nil
end
Vagrant.configure("2") do |config|

    config.vm.provider :virtualbox do |v|
        v.name = "bicpi-homepage"
        v.customize ["modifyvm", :id, "--memory", 1024]
     end

    config.vm.box = "ubuntu/trusty64"
    
    config.vm.network :private_network, ip: "192.168.33.99"
    config.vm.hostname = "homepage.vb"
    config.ssh.forward_agent = true


    #############################################################
    # Ansible provisioning (you need to have ansible installed)
    #############################################################

    
    if which('ansible-playbook')
        config.vm.provision "ansible" do |ansible|
            ansible.playbook = "ansible/playbook.yml"
            ansible.inventory_path = "ansible/inventories/dev"
            ansible.limit = 'all'
        end
    else
        config.vm.provision :shell, path: "ansible/windows.sh"
    end

    
    config.vm.synced_folder "./", "/var/www", type: "nfs"
    config.hostmanager.enabled = true
    config.hostmanager.manage_host = true
end

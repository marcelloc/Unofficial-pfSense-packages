# Unofficial-pfSense-packages
Unofficial packages for pfSense software

As many people know already, Netgate has trimmed a lot of packages from official repo since pfSense® 2.3. 

This repo updates some packages for newer pfSense software versions with manual procedure installs.

This is not supported by Netgate or pfSense team. Use it at your own risk.

Feedbacks and contributions are always welcome.

# Install instructions

You can enable unoffical repo creating or downloading the file below:

AMD64
pfSense 2.3 amd64:

```fetch -q -o /usr/local/etc/pkg/repos/Unofficial.conf https://raw.githubusercontent.com/marcelloc/Unofficial-pfSense-packages/master/Unofficial.conf```

pfSense 2.4 amd64:

```fetch -q -o /usr/local/etc/pkg/repos/Unofficial.conf https://github.com/marcelloc/Unofficial-pfSense-packages/blob/master/Unofficial.24.conf```


pfSense 2.3 x86:

```fetch -q -o /usr/local/etc/pkg/repos/Unofficial.conf https://raw.githubusercontent.com/marcelloc/Unofficial-pfSense-packages/master/Unofficiali386.conf```

Note: pfSense 2.4 is not available for x86 (32bit) systems.

After fetching the repo file, you will be able to see these packages under System -> Package Manager

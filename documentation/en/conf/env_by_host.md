# Environment by host configuration file

The files **env_by_host.php** in `/conf` directory is used by **Kernel** to
defines a different environment by the host been accessed.

This configuration file returns an array with a key pair where the key is a
regular expression to search the host and the value is the environment. This
configuration will replace the environment definition if the site host exists
in.

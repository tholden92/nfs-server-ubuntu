# Description of the Docker Image for NFS Server

The Docker image tholden92/nfs-server provides a ready-to-use NFS (Network File System) server, which is based on the Ubuntu OS. The image facilitates file sharing across a network in a seamless and efficient manner.

Customizing Users and Exports

You can customize the users and exports by setting the environment variables:

```console
USER_X: Define users. For instance, to define test-user, set the USER_0 variable.

USER_X: "username:uidxgid": To define a user with specific UID and GID. For instance, USER_1: "test:1005x1005" creates a user named test with UID and GID both set to 1005.

NFS_EXPORT_X: To define custom export parameters. For instance, the default configuration exports the /exports directory.
```

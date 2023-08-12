## Documentation for NFS Server Configuration

**1. User Creation**

You can create users for the NFS server by setting the USER_X environment variables.
Example Configurations:

USER_0:
Creates a user named test-user.

```yaml
USER_0: "test-user"
```
USER_1:
Creates a user named test with a specified UID and GID.

```yaml
USER_1: "test:1005x1005"
```
In this configuration:
    Username: test
    UID: 1005
    GID: 1005

Note: UID and GID are separated by the letter 'x'.

**2. Filesystem Exports**

To define the directories or filesystems the NFS server will share, use the NFS_EXPORT_X environment variables.

Example Configuration:

NFS_EXPORT_0:
Exports the /exports directory with specific permissions and configurations.

```yaml
NFS_EXPORT_0: "/exports *(rw,insecure,sync,no_subtree_check,fsid=0,no_root_squash)"
```

**3. Worker Threads**

To set the number of worker threads the NFS server will use, configure the NFS_THREADS environment variable.

Example Configuration:

NFS_THREADS:
Spawns 16 worker threads for handling client requests.

```yaml
NFS_THREADS: 16
```

**4. Full example**

```yaml
version: "3.9"

services:
  nfs-server:
    build:
      context: .
      dockerfile: Dockerfile
    restart: always
    ports:
      - "2049:2049"
    image: tholden92/nfs-server
    privileged: true
    volumes:
      - "/exports"
    environment:
      # Create a user named test-user
      USER_0: "test-user"
      # Creates a user named test with uid 1005 and gid 1005
      USER_1: "test:1005x1005"
      # Exports a filesystem
      NFS_EXPORT_0: "/exports *(rw,insecure,sync,no_subtree_check,fsid=0,no_root_squash)"
      # Spawn 16 worker threads
      NFS_THREADS: 16
```

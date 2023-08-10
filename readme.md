# NFS server

NFS server running in docker/kubernetes with Ubuntu as base.

## Example configuraton for docker-comopse

```
version: "3.9"

services:
    nfs-server:
        build:
            context: .
            dockerfile: Dockerfile
        restart: always
        image: tholden92/nfs-server
        privileged: true
        ports:
            - "2049:2049"
            - "635:635"
            - "1111:111"
        volumes:
            - "/exports"
        environment:
            NFS_EXPORT_0: "/exports *(rw,fsid=0,sync,no_subtree_check,no_auth_nlm,insecure,no_root_squash)"
            NUM_THREADS: 16
```

Running in Kubernetes is possible too, but will need to run in privileged mode.
# NFS Server

NFS server running in docker/kubernetes with Ubuntu as the base image.

## Example with docker-compose

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
        volumes:
            - "/exports"
        environment:
            NFS_EXPORT_0: "/exports *(rw,fsid=0,sync,no_subtree_check,no_auth_nlm,insecure,no_root_squash)"
            NUM_THREADS: 16
```

## Kubernetes

Container image is tested on GKE. Probably works fine on other cloud providers.

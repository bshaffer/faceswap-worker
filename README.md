# Faceswap App

This repository contains two services, the PHP FaceSwap frontend and the
Python FaceSwap backend. To deploy the application:

```
$ cd /path/to/faceswap-app
$ gcloud app deploy
$ cd worker
$ gcloud app deploy
```

This will deploy the `default` and `worker` services. Now go to the home
of your project to see the app running.
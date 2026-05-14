# Kubernetes with HelmForge

> [!NOTE]
> The HelmForge chart is a third-party Kubernetes installation option maintained by the HelmForge project.
> The Docker Compose setup remains the primary installation path documented by Statistics for Strava.

If you run applications on Kubernetes, you can deploy Statistics for Strava with the community Helm chart provided by
[HelmForge](https://helmforge.dev/docs/charts/strava-statistics/).

## Prerequisites

- A working Kubernetes cluster.
- Helm 3 installed locally.
- A default `StorageClass` or an existing PersistentVolumeClaim for the SQLite database and generated files.
- A Strava API application configured with the public URL where Statistics for Strava will be available.

## Add the HelmForge repository

```bash
helm repo add helmforge https://repo.helmforge.dev
helm repo update
```

## Create a values file

```yaml
# values.yaml
fullnameOverride: statistics-for-strava

strava:
  clientId: "YOUR_CLIENT_ID"
  clientSecret: "YOUR_CLIENT_SECRET"
  refreshToken: "YOUR_REFRESH_TOKEN_OBTAINED_AFTER_AUTH_FLOW"
  timezone: "Etc/GMT"

persistence:
  enabled: true
  size: 2Gi

service:
  type: ClusterIP
  port: 80
```

You can also reference an existing Kubernetes Secret instead of storing credentials in the Helm values file:

```yaml
strava:
  existingSecret: statistics-for-strava-credentials
  existingSecretClientIdKey: client-id
  existingSecretClientSecretKey: client-secret
  existingSecretRefreshTokenKey: refresh-token
```

## Install the chart

```bash
kubectl create namespace statistics-for-strava

helm install statistics-for-strava helmforge/strava-statistics \
  --namespace statistics-for-strava \
  --values values.yaml
```

For local access without an Ingress controller:

```bash
kubectl port-forward \
  --namespace statistics-for-strava \
  svc/statistics-for-strava 8080:80
```

Then open `http://localhost:8080/`.

## Expose with Ingress

For a public installation, configure an Ingress and use the same hostname in your Strava API settings:

```yaml
ingress:
  enabled: true
  ingressClassName: nginx
  hosts:
    - host: strava.example.com
      paths:
        - path: /
          pathType: Prefix
  tls:
    - hosts:
        - strava.example.com
      secretName: statistics-for-strava-tls
```

## Import and build statistics

After the application is authorized with Strava, you can run the same console commands inside the Kubernetes pod:

```bash
kubectl exec \
  --namespace statistics-for-strava \
  deploy/statistics-for-strava \
  -- bin/console app:strava:import-data

kubectl exec \
  --namespace statistics-for-strava \
  deploy/statistics-for-strava \
  -- bin/console app:strava:build-files
```

## More information

- [HelmForge Statistics for Strava chart documentation](https://helmforge.dev/docs/charts/strava-statistics/)
- [HelmForge chart source](https://github.com/helmforgedev/charts/tree/main/charts/strava-statistics)

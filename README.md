# dennis

`dennis` is a command line tool for managing your DNS records. It can manage DNS records for multiple accounts and providers, including:

-   [Cloudflare](https://www.cloudflare.com/)
-   [DigitalOcean](https://www.digitalocean.com/)
-   [GoDaddy](https://www.godaddy.com/)
-   [Route53](https://aws.amazon.com/route53/) (Coming Soon)

## Installation

```bash
composer global require joetannenbaum/dennis
```

## Adding an Account

```bash
dennis accounts:add
```

## Adding or Updating a Record

```bash
dennis records:add
```

## Listing DNS Records

```bash
dennis records:list
```

## Updating Nameservers

```bash
dennis nameservers:update
```

## Roadmap

-   [ ] Support Route53
-   [ ] Configurable sets of records to add
-   [ ] Ability to connect directly domains directly to droplets, servers, load balancers, etc

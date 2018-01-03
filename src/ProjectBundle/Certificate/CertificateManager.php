<?php
/**
 * Created by PhpStorm.
 * User: gseidel
 * Date: 02.01.18
 * Time: 14:21
 */

namespace ProjectBundle\Certificate;

use ProjectBundle\Entity\Host;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Filesystem\Filesystem;

class CertificateManager
{
    use ContainerAwareTrait;

    public function dumpCertificates()
    {
        $fs = new Filesystem();
        /** @var Host[] $hosts */
        $hosts = $this->container->get('project.repository.host')->findAll();
        foreach($hosts as $host) {
            if($host->getCertificate()) {
                $fs->dumpFile($this->getCertificatePath($host), $host->getCertificate());
                $fs->dumpFile($this->getCertificateKeyPath($host), $host->getCertificateKey());
            }
        }
    }

    public function getCertificatePath(Host $host)
    {
        $path = sprintf('%s/%s.crt', $this->container->getParameter('certificate_path'), $host->getDomain());
        return $path;
    }

    public function getCertificateKeyPath(Host $host)
    {
        $path = sprintf('%s/%s.key', $this->container->getParameter('certificate_path'), $host->getDomain());
        return $path;
    }

    public function renewCertificates()
    {
        /** @var Host[] $hosts */
        $hosts = $this->container->get('project.repository.host')->findBy([
            'certificateType' => Host::CERTIFICATE_TYPE_LETS_ENCRYPT
        ]);

        foreach($hosts as $host) {
            $this->renewCertificate($host);
        }

        $this->container->get('doctrine.orm.entity_manager')->flush();
    }

    public function renewCertificate(Host $host)
    {
        $client = $this->getClient();
        $client->signDomains([$host->getDomain()]);

        $fs = $this->container->get('filesystem');
        $keyPath = sprintf('%s/%s/private.pem', $this->getCertStorage(), $host->getDomain());
        $certPath = sprintf('%s/%s/fullchain.pem', $this->getCertStorage(), $host->getDomain());
        $requestPath = sprintf('%s/%s/last.csr', $this->getCertStorage(), $host->getDomain());

        $host->setCertificate($fs->readFile($certPath));
        $host->setCertificateKey($fs->readFile($keyPath));
        $host->setCertificateRequest($fs->readFile($requestPath));
    }

    public function getToken($token)
    {
        $path = sprintf('%s/.well-known/acme-challenge/%s', $this->getDocumentRoot(), $token);
        $fs = $this->container->get('filesystem');
        if($fs->exists($path)) {
            return $fs->readFile($path);
        }
        return null;
    }

    /**
     * @return Lescript
     */
    private function getClient()
    {
        $client = new Lescript($this->getCertStorage(), $this->getDocumentRoot());
        $client->initAccount();
        return $client;
    }

    /**
     * @return string
     */
    private function getCertStorage()
    {
        $fs = $this->container->get('filesystem');
        $dir = __DIR__.'/../../../build/cert';
        if(!$fs->exists($dir)) {
            $fs->mkdir($dir);
        }
        return $dir;
    }

    /**
     * @return string
     */
    private function getDocumentRoot()
    {
        $fs = $this->container->get('filesystem');
        $dir = __DIR__.'/../../../build/www';
        if(!$fs->exists($dir)) {
            $fs->mkdir($dir);
        }
        return $dir;
    }
}
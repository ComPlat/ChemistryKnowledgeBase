<?php
namespace DIQA\ChemExtension\Endpoints;

use FSFile;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Title;
use Wikimedia\ParamValidator\ParamValidator;

class UploadFileToWiki extends SimpleHandler {

    public function run() {

        $params = $this->getValidatedParams();

        $localRepo = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo();
        $fileData = $this->getRequest()->getBody()->getContents();


        $fileTitle = Title::newFromText($params['fileName'], NS_FILE);
        if (!$fileTitle->isValid()) {
            $res = new Response('Invalid file name');
            $res->setStatus(400);
            return $res;
        }
        $tmpFile = sys_get_temp_dir() . '/'. uniqid();
        file_put_contents($tmpFile, $fileData);

        $file = $localRepo->newFile($fileTitle);
        if ($file->exists()) {
            $hashOld = md5(file_get_contents($file->getLocalRefPath()));
            $hashNew = md5($fileData);
            if ($hashOld === $hashNew) {
                $res = new Response();
                $res->setStatus(200);
                return $res;
            }
        }
        $status = $file->upload(new FSFile($tmpFile), "uploaded by user", "");
        unlink($tmpFile);

        if (!$status->isOK()) {
            $res = new Response('Upload failed: ' . $status->getMessage()->text());
            $res->setStatus(500);
            return $res;
        }

        $res = new Response();
        $res->setStatus(200);
        return $res;
    }

    public function needsWriteAccess() {
        return false;
    }

    public function getParamSettings() {
        return [
            'fileName' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],

        ];
    }
}
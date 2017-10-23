<?php

class SeoScript
{
    /**
     * The main method which compares all required titles and descriptions from the csvData.csv
     * with meta tags title and description on required sites.
     *
     * Result of the work is printed out an array with errors or the message 'Everything's OK'.
     */
    final public function checkMetaTags()
    {
        $fileName = "csvData.csv";

        $csvData = $this->readCsvFile($fileName);

        $errors = array();

        for ($i = 1; $i < count($csvData); $i++) { //read all lines except the line with csv headers
            $csvLine = $csvData[$i];

            $url = $csvLine[0]; //url
            $title = $csvLine[1]; //title
            $description = $csvLine[2]; //description

            $siteContent = $this->getSiteContent("$url");

            $metaTitle = $this->getMetaTagTitle($siteContent);

            $metaDescription = $this->getMetaTagDescription($siteContent);

            if (strcmp($metaTitle, $title) !== 0) {
                array_push($errors,"URL: {$url}. Meta title \"".$metaTitle."\" does not compare with required title of this site: {$title}");
            }

            if (strcmp($metaDescription, $description) !== 0) {
                array_push($errors, "URL: {$url}. Meta description \"".$metaDescription."\" does not compare with required description of this" .
                    " site: {$description}");
            }
        }

        $this->printResults($errors);
    }

    /*
     * Prints result in a good view.
     */
    private function printResults($errors)
    {
        echo '<pre>';
        if ($errors == null) {
            echo 'Everything OK!';
        } else {
            print_r($errors);
        }
        echo '</pre>';
    }

    /*
     * The function reads a csv file
     * @return array - csv data
     */
    private function readCsvFile($fileName)
    {
        $csvData = array();

        if (file_exists($fileName)) {
            $handleForFile = fopen($fileName, "r");
            while (($data = fgetcsv($handleForFile)) !== FALSE) {
                $csvData[] = $data;
            }
            fclose($handleForFile);
        } else {
            throw new IllegalArgumentException("{$fileName} does not exist. Be sure that the file is located in the path");
        }

        return $csvData;
    }

    /*
     * Returns the title value of the title tag
     */
    private function getMetaTagTitle($siteContent)
    {
        if ($siteContent == null) {
            throw new IllegalArgumentException("The variable site content is null. Check the site.");
        }
        $domDocument = $this->getDomDocumentBySiteContentAsAString($siteContent);
        $tagTitle = $domDocument->getElementsByTagName('title');
        if ($tagTitle !== null) {
            $tagTitleValue = $tagTitle->item(0)->nodeValue;
            return $tagTitleValue;
        } else {
            return "";
        }
    }

    /*
     * Returns the value of the meta's description content
     */
    private function getMetaTagDescription($siteContent)
    {
        if ($siteContent == null) {
            throw new IllegalArgumentException("The variable site content is null. Check the site.");
        }
        $metaTags = $this->getMetaTags($siteContent);
        if (isset($metaTags['description'])) {
            return $metaTags['description'];
        }
        return "";
    }

    /*
     * Returns all meta tags from the site content
     */
    private function getMetaTags($siteContent)
    {
        $pattern = '
        ~<\s*meta\s
        
        (?=[^>]*?
        \b(?:name|property|http-equiv)\s*=\s*
        (?|"\s*([^"]*?)\s*"|\'\s*([^\']*?)\s*\'|
        ([^"\'>]*?)(?=\s*/?\s*>|\s\w+\s*=))
        )
        
        [^>]*?\bcontent\s*=\s*
        (?|"\s*([^"]*?)\s*"|\'\s*([^\']*?)\s*\'|
        ([^"\'>]*?)(?=\s*/?\s*>|\s\w+\s*=))
        [^>]*>
        
        ~ix';

        if (preg_match_all($pattern, $siteContent, $out))
            return array_combine($out[1], $out[2]);
        return array();
    }

    /*
     * Returns the site's content
     */
    private function getSiteContent($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("{$url} is not an url. Check that the url is valid");
        }

        $siteContent = null;

        /*
         * Could not use the get_meta_tags($url) method
         * because need to through out a
         * cookie test='seo'
         */
        try {
            $resourceHandle = curl_init();

            curl_setopt($resourceHandle, CURLOPT_URL, $url);
            curl_setopt($resourceHandle, CURLOPT_HEADER, 0);
            curl_setopt($resourceHandle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($resourceHandle, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($resourceHandle, CURLOPT_COOKIE, 'test="seo"');

            $siteContent = curl_exec($resourceHandle);
            curl_close($resourceHandle);
        } catch (Exception $e) {
            throw new Exception("Failed to load {$url}" . "Exception " . $e->getMessage());
        }

        return $siteContent;
    }

    /*
     * Returns DOM Document by a string with HTML text
     */
    private function getDomDocumentBySiteContentAsAString($siteContent)
    {
        $document = new DOMDocument();
        @$document->loadHTML($siteContent);
        return $document;
    }
}

$seoScript = new SeoScript();
$seoScript->checkMetaTags();
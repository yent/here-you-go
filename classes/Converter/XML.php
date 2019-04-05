<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\Converter;


class XML {
    /**
     * Convert XML into raw data
     *
     * @param mixed $source string or DOMElement
     *
     * @return mixed
     *
     * TODO validation
     */
    public static function parse($source) {
        if(is_string($source)) {
            $doc = new \DOMDocument();
            $doc->loadXML($source);
            $doc->normalize();
            
            $source = $doc->childNodes[0];
        }
        
        $type = $source->getAttribute('type');
        if($type === 'object') {
            $data = [];
            foreach($source->childNodes as $node) {
                if($node->nodeType === XML_TEXT_NODE) continue;
                
                $data[$node->nodeName] = self::parse($node);
            }
            
            return (object)$data;
        }
        
        if($type === 'array') {
            $items = [];
            foreach($source->childNodes as $node) {
                /** @var \DOMElement $node */
                if($node->nodeType === XML_TEXT_NODE) continue;
                
                if($node->hasAttribute('index') && is_int($node->getAttribute('index'))) {
                    $items[(int)$node->getAttribute('index')] = self::parse($node);
                    
                } else {
                    $items[] = self::parse($node);
                }
            }
            
            return $items;
        }
        
        if($type === 'boolean')
            return (bool)$source->nodeValue;
        
        if($type === 'integer')
            return (int)$source->nodeValue;
        
        if($type === 'float')
            return (float)$source->nodeValue;
        
        
        if($type === 'string') {
            $txt = $source->textContent;
            
            // CDATA ?
            if($source->hasChildNodes()) {
                $node = $source->childNodes[0];
                
                if($node->nodeType == XML_CDATA_SECTION_NODE)
                    $txt = $node->textContent;
            }
            
            return $txt;
        }
        
        // Untyped data, try to guess ...
        
        $scalar = true;
        foreach($source->childNodes as $node)
            $scalar &= ($node->nodeType === XML_TEXT_NODE);
        
        if($scalar) {
            $value = $source->textContent;
            
            if(preg_match('`^-?[1-9][0-9]*$`', $value))
                return (int)$value;
            
            if(preg_match('`^-?[0-9]+\.[0-9]+$`', $value))
                return (float)$value;
            
            return (string)$value;
        }
        
        // Not scalar
        $entries = [];
        foreach($source->childNodes as $node) {
            if($node->nodeType === XML_TEXT_NODE) continue;
            
            if(array_key_exists($node->nodeName, $entries)) {
                if(!is_array($entries[$node->nodeName]))
                    $entries[$node->nodeName] = (array)$entries[$node->nodeName];
            }
            
            $value = self::parse($node);
            
            if(array_key_exists($node->nodeName, $entries) && is_array($entries[$node->nodeName])) {
                $entries[$node->nodeName][] = $value;
                
            } else {
                $entries[$node->nodeName] = $value;
            }
        }
        
        return $entries;
    }
    
}
<?php
/**
 * Created by PhpStorm.
 * User: exodus4d
 * Date: 14.02.15
 * Time: 21:28
 */

namespace Data\Mapper;

class CcpSystemsMapper extends \RecursiveArrayIterator {

    static $map = [
        'system_id' => 'systemId',
        'system_name' => 'name',
        'system_security' => 'trueSec',
        'connstallation_id' => ['constellation' => 'id'],
        'constallation_name' => ['constellation' => 'name'],
        'region_id' => ['region' => 'id'],
        'region_name' => ['region' => 'name']
    ];

    function __construct($data){

        parent::__construct($data, \RecursiveIteratorIterator::SELF_FIRST);
    }

    /**
     * get formatted data
     * @return array
     */
    public function getData(){

        // format functions
        self::$map['effect'] = function($iterator){

            $effect = $iterator['effect'];

            switch($iterator['effect']){
                case 'magnetar':
                    $effect = 'magnetar';
                    break;
                case 'red giant':
                    $effect = 'redGiant';
                    break;
                case 'pulsar':
                    $effect = 'pulsar';
                    break;
                case 'wolf-rayet star':
                    $effect = 'wolfRayet';
                    break;
                case 'cataclysmic variable':
                    $effect = 'cataclysmic';
                    break;
                case 'black hole':
                    $effect = 'blackHole';
                    break;
            }

            return $effect;
        };

        // format functions
        self::$map['security'] = function($iterator){

            if(
                $iterator['security'] == 7 ||
                $iterator['security'] == 8 ||
                $iterator['security'] == 9
            ){
                $trueSec = round($iterator['trueSec'], 3);

                if($trueSec <= 0){
                    $security = '0.0';
                }elseif($trueSec < 0.5){
                    $security = 'L';
                }else{
                    $security = 'H';
                }
            }else{
                $security = 'C' . $iterator['security'];
            }

            return $security;
        };

        // format functions
        self::$map['type'] = function($iterator){

            // TODO refactor
            $type = 'w-space';
            $typeId = 1;
            if(
                $iterator['security'] == 7 ||
                $iterator['security'] == 8 ||
                $iterator['security'] == 9
            ){
                $type = 'k-space';
                $typeId = 2;

            }

            return [
                'id' => $typeId,
                'name' => $type
            ];
        };

        iterator_apply($this, 'self::recursiveIterator', [$this]);


        return iterator_to_array($this, false);
    }

    /**
     * recursive iterator function called on every node
     * @param $iterator
     * @return mixed
     */
    static function recursiveIterator($iterator){

        while ( $iterator -> valid() ) {
            if ( $iterator->hasChildren() ) {
                $iterator->offsetSet($iterator->key(), self::recursiveIterator( $iterator->getChildren() )->getArrayCopy() );
            }else {

                while( $iterator -> valid() ){

                    // check for mapping key
                    if(array_key_exists($iterator->key(), self::$map)){

                        if(is_array(self::$map[$iterator->key()])){
                            // a -> array mapping

                            $parentKey = array_keys( self::$map[$iterator->key()] )[0];
                            $entryKey = array_values( self::$map[$iterator->key()] )[0];

                            // check if key already exists
                            if($iterator->offsetExists($parentKey)){
                                $currentValue = $iterator->offsetGet($parentKey);
                                // add new array entry
                                $currentValue[$entryKey] = $iterator->current();

                                $iterator->offsetSet( $parentKey, $currentValue  );
                            }else{
                                $iterator->offsetSet( $parentKey, [$entryKey => $iterator->current() ]  );
                            }

                            $removeOldEntry = true;
                        }elseif(is_object(self::$map[$iterator->key()])){
                            // a -> a (format by function)

                            $formatFunction = self::$map[$iterator->key()];

                            $iterator->offsetSet( $iterator->key(), call_user_func($formatFunction, $iterator)  );

                            // just value change no key change
                            $removeOldEntry = false;
                            $iterator -> next();
                        }else{
                            // a -> b mapping
                            $iterator->offsetSet( self::$map[$iterator->key()], $iterator->current() );

                            $removeOldEntry = true;
                        }

                        // remove "old" entry
                        if($removeOldEntry){
                            $iterator->offsetUnset($iterator->key());
                        }

                    }else{
                        // continue with next entry
                        $iterator -> next();
                    }

                }
            }

            $iterator -> next();
        }

        return $iterator;
    }

} 
<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsService.php';
        
    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsKeyPair extends CDH_abstractAwsService {


        public function __construct(){
            $this->setCredentials();
            $this->setClientIam();
            $this->setClientEc2();
            $this->setClassName("CDH_awsKeyPair");
        }
        
        public function create ( $data = null, $sshAccessFilePath = null ) {

            $client = $this->getClientEc2();
            #$id     = $data['DBInstanceIdentifier'];
            $name   = $data['KeyName'];
            
            try{
                if ( isset($id) && $this->existsById( $id ) )
                    throw new Exception( "L044 El servicio " . $this->getClassName() . " con la id " . $id . " ya existe");
                
                if ( isset($name) && $this->existsByName( $name ) )
                    throw new Exception( "L047 El servicio " . $this->getClassName() . " con el nombre " . $name . " ya existe");
                
                $result     = $client->createKeyPair( $data );
                $identifier = $data['KeyName'];
                if( $this->waitToBeCreated(  $identifier  ) == false )
                    throw new Exception( "L054 Demasiado tiempo esperando a la creación del servicio " . $this->getClassName() );
                
                if( file_put_contents( $sshAccessFilePath, $result['KeyMaterial'] ) ==  false )
                    throw new Exception( "L043 error " . $this->getClassName() . " creando sshkey");
                return true ;
            
            }catch (AwsException $e) {
                $this->logAdmin(  "L059 Error  " . $this->getClassName() . "->create()" );  
                $this->logAdmin(  "\n Mensaje de error:" );
                $this->logAdmin(  $e->getMessage() );
                return false;
            }catch(Exception $e){
                $this->logAdmin(  "L064 Error  " . $this->getClassName() . "->create()" );  
                $this->logAdmin(  "\n Mensaje de error:" );
                $this->logAdmin(  $e->getMessage() );
                return false;
            }

        }
        

        


        public function createOld( $data = null, $sshAccessFilePath = null ) {
            if( !isset ( $data['KeyName'] )  )
                return false;

            if ( $this->existsByName( $data['KeyName'] ) )        
                return true;
            

            $client = $this->getClientEc2();

            try{
                
                $result = $client->createKeyPair( $data );

                                    
            }catch (AwsException $e) {
                echo $e->getMessage();
                return false;
            }catch(Exception $e){
                echo $e->getMessage();
                return false;
            }

            if( $this->waitToBeCreated(  $data['KeyName']  ) == false ){
                echo "KO;KEYPAIR;002;104";
                return false; 
            }


            if( file_put_contents( $sshAccessFilePath, $result['KeyMaterial'] ) ==  false ){
                echo "\nL121 - no se puedo escribir en fichero";
                return false;
            }

            #if( $sshAccessFilePath != null ){
            #    file_put_contents( $sshAccessFilePath, $result['KeyMaterial'] );
                
            #}

            return true;

        }

        public function createSshAccessFile( $filePath, $result ){
            #file_put_contents( "store/cdh_dom_keyPairForAmiInstance.txt", $result['KeyMaterial'] );
            file_put_contents( $filePath, $result['KeyMaterial'] );

        }
        
       

        /**
         * Esta función carece de sentido, pues los únicos valores interesantes son el Nombre y la Id y ambos los tenemos
         *  
         * */
        public function getFieldValueById ( $field, $id ) {
            
            if( $field != true || $id != true )
                return false;

            $fields = $this->getFields();
            $service = $this->getServiceById( $id );

            if ( in_array( $field, $fields ) && $service != false ) 
                return $service[$field];

            return false;               

        }

        /**
         * Esta función busca el valor de un campo del array. Existen campos con igual nombre
         * pero en distinto nivel del array. si el campo buscado está en el primer nivel o es único
         * la función trabaja bien.
         * 
         * En los casos en que el campo esté repetido, pero en distinto nivel, es necesario insertar 
         * el argumento $cloudObject como un array interno con campos únicos   
         * 
         * @param string $field cualquier valor contenido en el HostedZone - Realmente sólo puede captar la Id
         *                  
         * 'Id'                          (cadena)
         * 
         * @param string $name - Nombre de la Subnet
         * 
         * @return string|array|boolean
         * 
         * */


        public function getFieldValueByName ( $field, $name ){
            

            if( $field != true || $name != true )
                return false;

            $fields = $this->getFields();
            $i = $this->in_array_ic ($field, $fields);
            if( $i == false ) 
                return false;
            
            $field = $fields[$i];
            $service = $this->getServiceByName($name);
            if ($service == false)
                return false;
            return $service[$field];


        }

        public function getTagValueByName( $tagKey, $name) {
            return false;
            
            if( $tagKey != true || $name != true )
                return false;
            $service = $this->getServiceByName( $name );

            if(!is_array ($service) )
                return false;
            $tags = $service['Tags'];
            foreach( $tags as $tag ) {
                if( strcmp ($tag['Key'], $tagKey)  == 0)
                    return $tag['Value'];
            }
            return false;
        }


        public function getTagValueById( $tagKey, $id ) {
            return false;
            if( $tagKey != true || $id != true )
                return false;

            $service = $this->getServiceById( $id );

            if(!is_array ($service) )
                return false;

            $tags = $service['Tags'];

            foreach( $tags as $tag ) {
                if(  isset( $tag['Key'] ) && strcmp ($tag['Key'], $tagKey)  == 0 )
                    return $tag['Value'];
            }
            return false;
        }


        /**
         * Esta función devuelve todos los elementos de un servicio cloud de una id determinada
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         */


        public function getServiceById( $dato ){
            
            if($id != true)
                return false;

            $client = $this->getClientEc2();
            
            try{
                $result = $client->describeKeyPairs([]);
            }catch( AwsException $e){
                return false;
            }

            if( !is_object($result) || !is_array( $result['KeyPairs'] )  )
                return false;

            $services = $result['KeyPairs'];

            foreach ( $services as $service ){
                if( strcmp ($service['KeyPairId'], $dato ) == 0 ) 
                    return $service;
            }    

            return false;
        }

        
        /**
         * Esta función devuelve todos los elementos de un servicio cloud con un nombre determinado.
         * El objeto VPC carece del parámetro VpcName, por lo que busca en los campos Tag 
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         */

        public function getServiceByName( $data  ){
            
            if($data != true)
                return false;
                
            $client = $this->getClientEc2();
            
            try{
                $result = $client->describeKeyPairs([]);
            }catch( AwsException $e){
                return false;
            }

            if( !is_object($result) || !is_array( $result['KeyPairs'] )  )
                return false;

            $services = $result['KeyPairs'];

            foreach ( $services as $service )
                if( strcmp ($service['KeyName'], $data ) == 0 ) 
                    return $service;
            
            return false;
            
        }

        /**
         * Esta función devuelve todos los elementos de un servicio cloud con un nombre determinado.
         * El objeto VPC carece del parámetro VpcName, por lo que busca en los campos Tag.
         * Esta función es idéntica a getServiceByName y se ha implementado por tema de compatibilidad
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         * */

        public function getServiceByTagName( $name = null ){

            if($name != true)
                return false;

            $client = $this->getClientEc2();
            
            try{
                $result = $client->describeKeyPairs([
                    #'Filters' => [
                    #    [
                    #        'Name' => 'tag:Name',
                    #        'Values' => [$name],
                    #    ]
                    #] 
                ]);
        
            }catch( AwsException $e){ 
                return false;
            }

            if( !is_object($result ) || !is_array( $result ['KeyPairs'] )    )
                return false;

            $services = $result['KeyPairs'];
            
            foreach( $services as $service ){
                $tags = $service['Tags'];
                foreach( $tags as $tag )
                    if( $tag['Key'] == 'Name' && $tag['Value'] == $name)
                        return $service;
            }

            return false;
        }

        public function delete( $data ){

            if( !$this->exists($data) )
                #echo PHP_EOL . "bucket no existe " . PHP_EOL;
                return true;
                
            try{
                $client = $this->getClientEc2();
                $result = $client->deleteKeyPair([
                    'KeyName' => $data,
                    #'KeyPairId' => '<string>',
                ]);
                
                return $this->waitToBeDeleted( $data  );

            }catch(AwsException $e){
                $e->getMessage();
                return false;
            }

        }

        public function deleteFile(  $f ){
            try{
                ulink($f);
            }catch(Exception $e){
                echo "\nL394 - Imposible borrar $f \n";
                return false;
            }
            return true;
        }



        public function exists( $name ){
            return $this->getServiceByName(  $name );
        }

        public function existsById( $id ){
            return false;

            if( $id != true)
                return false;

            $service = $this->getServiceById( $id );
            if( $service == false)
                return false;

            return true;

        }


        public function existsByName( $name ){
            
            #return $this->getServiceByName(  $name );

            if( $name != true)
                return false;
            
            $service = $this->getServiceByName( $name );

            if( $service != false && is_array ( $service ) )
                return true;

            return false;

        }


        /**
         * @param string    $name    valoer del tag "Name"
         * @param int       $seconds    Tiempo que duerme el itinerador
         * @param int       $times      Número máximo de comprobaciones
         * 
         */

        public function waitToBeCreated( $name, $seconds = null, $times = null ){
            
            if($seconds != true)
                $seconds = 10;
        
            if($times != true)
                $times = 6;
            
            #$clientEc2 = $this->getClientEc2();

            for($i = 0; $i < $times;  $i++ ){
                $service = $this->getServiceByName( $name );
                #$service = $this->getServiceById( $id );
                #var_export( $ig );
                if( is_array( $service )  )
                    return true;                
                sleep($seconds);
            }

            return false;
        }

        public function waitToBeDeleted( $data, $seconds = null, $times = null ){

            if($seconds != true)
                $seconds = 10;
    
            if($times != true)
                $times = 6;
            
            for($i = 0; $i < $times;  $i++ ){
                $service = $this->getServiceByName( $data );
                if( $service == false  )
                    return true;                
                sleep($seconds);
            }

            return false;
        }



        /**
         * Consigue la id dando el nombre como parámetro.
         * 
         * La id de un hostedzone es un caso curioso pues tiene este formato:
         *  /hostedzone/Z[string]       
         * 
         * Pero en la mayoría de los lugares que se precisa la id de un hostedzone se requiere Z[string]
         * 
         * @param string $name N ombre del hostedzone a buscar
         * @return string Z[string] . Se eliminia la cadena '/hostedzone/'           
         * 
         * */

        public function getId() {
            return false;
            return $this->id;
        }

        public function getIdByName( $name ) {
            $client = $this->getClientEc2();
            $result = $client->describeKeyPairs([
                'KeyNames' => [$name],
            ]);

            if( !isset( $result['KeyPairs'] ) || !is_array( $result['KeyPairs'] ) || count( $result['KeyPairs']) != 1)
                return false;

            return $result['KeyPairs'][0]['KeyPairId'];

        }

        public function setId( $id ) {
            $this->id = $id;
        }

        public function getFields(){
            return false;

            return $this->fields;
        }

        public function getStatus ($id ){
            return false;

            $service = $this->getServiceById( $id );
            
            if ( isset( $service['LifeCycleState'] )  )
                return $service['LifeCycleState'];
            return false;
        
        }


        public function getStatusById ($id ){
            return false;

            return $this->getStatus( $id );
        }


        public function getStatusByName ($name ){
            return false;
            
            $service = $this->getServiceByName( $name );
            
            return $this->getStatus(  $service[ 'FileSystemId' ]  );



            #if ( $service == false)
            #    return false;

            #return $service['State'];

        }




    }

?>

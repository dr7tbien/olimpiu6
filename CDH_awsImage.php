<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */

    require_once 'vendor/autoload.php';
    require_once 'defines/CDH_awsDefinesAll.php';
    #require_once 'defines/services_aws/CDH_awsDefineImage.php';
    #require_once 'defines/services_aws/CDH_awsDefineDBInstance.php';

    require_once 'defines/CDH_definesErrorMessages_ES.php';
    //require_once 'classes/CDH_PS_userDataChecker.php';
    #require_once 'classes/CDH_abstractAwsService.php';
    
    require_once 'classes/CDH_awsVpc.php';        
    require_once 'classes/CDH_awsBucket.php';        
    require_once 'classes/CDH_awsSecurityGroup.php'; 
    require_once 'classes/CDH_awsPolicy.php'; 
    require_once 'classes/CDH_awsSubnet.php'; 
    
    require_once 'classes/CDH_awsEfs.php';
    require_once 'classes/CDH_awsRole.php';
    require_once 'classes/CDH_awsKeyPair.php';
    require_once 'classes/CDH_awsInstance.php';
    require_once 'classes/CDH_awsInstanceProfile.php';

    //incluir cloudatorHelper
    #require_once __DIR__  . '/inc/cloudatorHelper.php';
    #require_once 'classes/inc/cloudatorHelper.php';	


    #require_once 'classes/CDH_awsSubnet.php';        
    #require_once 'classes/CDH_awsMountTarget.php';   
    #require_once 'classes/CDH_awsAccessPoint.php';   
         


    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsImage extends CDH_abstractAwsService  {
        
        public function __construct(){
            $this->setCredentials();
            $this->setClientS3();
            $this->setClientEc2();
            $this->setClassName("CDH_awsImage");
        }
        
        /**
         * Esta función crea un objeto suibnet de AWS.
         * Si existe carga los datos del existente en el objeto
         * 
         * @param string $pos ['0', '1', '2']
         * 
         * @param string $type ['public', 'private', 'database']
         * 
         * @return array serviceAWS un array copn los contenidos de la subnet
         * 
         */



        public function deleteForCloudator(){
            $bucket         = new CDH_awsBucket();
            $policy         = new CDH_awsPolicy();
            $role           = new CDH_awsRole();
            $instanceProfile = new CDH_awsInstanceProfile();
            
            $securityGroup  = new CDH_awsSecurityGroup();
            $keyPair        = new CDH_awsKeyPair();            
            $instance       = new CDH_awsInstance();
            
            $resultBuckets = $bucket->deleteBucketStartsWith ( CDH_IMAGE_BUCKET_ROOT_NAME               );

            echo "\nL121 - Deleting\n";        


            $imageId = $this->getIdByName( CDH_IMAGE['Name'] );

            if( $imageId != false ){
                $resultImage = $this->delete( $imageId );

            }

            echo "\nL131 - Image Deleted\n";        
            

            if( $instance->exists( 'CDH_IMAGE_' . CDH_DOMINIO_SIN_PUNTO ) ) {
                $resultInstance = $instance->delete( 'CDH_IMAGE_' . CDH_DOMINIO_SIN_PUNTO );
            }

            echo "\nL132 - Instance Deleted\n";        
            #var_export( $resultInstance );

            if( $securityGroup->existsByName( CDH_IMAGE_SG['GroupName' ] ) ) {
                $sgId           = $securityGroup->getIdByName( CDH_IMAGE_SG['GroupName' ]   );
                $resultSg       = $securityGroup->delete( $sgId );
            }
    
            echo "\nL139 - SG Deleted\n";        
            #var_export( $resultSg );

            if( $policy->exists( CDH_IMAGE_POLICY['PolicyName'] ) ) {

                $policyArn         = $policy->getFieldValueByName('Arn', CDH_IMAGE_POLICY['PolicyName']  );
                $CDH_IMAGE_detachRolePolicy = CDH_IMAGE_detachRolePolicy;
                $CDH_IMAGE_detachRolePolicy['PolicyArn']  = $policyArn;
                $role->detachRolePolicy ( $CDH_IMAGE_detachRolePolicy );

                $resultPolicy = $policy->delete       ( CDH_IMAGE_POLICY['PolicyName'   ]   );
 
            }

            echo "\nL152 - Policy Deleted\n";        
            #var_export( $resultPolicy );

            if( $role->exists(CDH_IMAGE_ROLE['RoleName']) ){
                $role->removeRoleFromInstanceProfile( CDH_IMAGE_removeRoleFromInstanceProfile );
                $resultRole = $role->delete         ( CDH_IMAGE_ROLE['RoleName'       ]   );
            }

            echo "\nL159 - Role Deleted\n";        
            #var_export( $resultRole );

            if( $instanceProfile->exists( CDH_IMAGE_ROLE['RoleName'] )  ) {
                    $instanceProfile->deleteInstanceProfile( CDH_IMAGE_INSTANCE_PROFILE['InstanceProfileName'] ); 
            }

            echo "\nL165 - InstancePolicy Deleted\n";        
            #var_export( $resultRole );

            $resultKeyPair = $keyPair->delete      ( CDH_IMAGE_KEYPAIR['KeyName'     ]   );

            echo "\nL169 - Key Deleted\n";        
            #var_export( $resultKeyPair );



            echo "\nL165 - Everything Delete\n";        

        }



        public function deleteServices(){



        }

        
        public function createUserDataFile( $data = [] ) {

            $dataKeys   = array_keys        ($data);
            $dataValues = array_values      ($data);
            $texto      = file_get_contents ($data['CDH_IMAGE_FILE_USER_DATA_TEMPLATE'] );
            foreach ( $data as $key=>$value ) {

                $texto = preg_replace( '/'.$key.'/', $value, $texto, 1, $count  );
                if( $count > 1 ){
                    echo "\nL346 \$count = $count - \$key = $key \n";
                    return false;
                }

            }

            file_put_contents( $data['CDH_IMAGE_FILE_USER_DATA'], $texto );

            return true;

        }

        /**
         * @param string $name Nmbre del bucket
         * 
         * @param string $key TagKey a buscar
         * 
         * @param string $value ValueKey a comparar
         * 
         */

        public function waitInstanceUserDataFinished( $name, $key, $value ){

            echo "\n\$name = |$name| | \$key = |$key| | \$value = $value"; 
            

            $bucket = new CDH_awsBucket();
            $times  = 100;
            $time   = 36;
            
            for( $i = 0;  $i < $times ; $i++ ) {

                $tagValue = $bucket->getTagValueByName( $name, $key);
                echo "\n\$tagValue = |$tagValue| | \$value = |$value|"; 
                if ( strcmp( $tagValue, $value ) == 0 )
                    return true;

                sleep( $time);    
            }
            

            return false;
            

        }
        

        public function create ( $data = [] ) {

            $client = $this->getClientEc2();
            #$id     = $data['DBInstanceIdentifier'];
            #$name   = $data['Bucket'];
            
            try{
                #if ( isset($id) && $this->existsById( $id ) )
                #    throw new Exception( "L044 El servicio " . $this->getClassName() . " con la id " . $id . " ya existe");
                
                #if ( isset($name) && $this->existsByName( $name ) )
                #    throw new Exception( "L047 El servicio " . $this->getClassName() . " con el nombre " . $name . " ya existe");
                
                $result     = $client->createImage( $data );
                #$service    = $result['HostedZone'];
                $identifier = $result['ImageId'];

                if( $this->waitToBeCreated(  $identifier  ) == false )
                    throw new Exception( "L054 Demasiado tiempo esperando a la creación del servicio " . $this->getClassName() );

                return true;
            
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


        public function createOld($data = [] ) {

            if( !is_array( $data) )
                return false;
            /*
            if ( $this->existsByName( $data['Name'] ) ) {        
                $service = $this->getServiceByName( $data['Name'] );
                #die("\n \$data['Name'] = " . $data['Name'] .  " \$service['Name'] = " . $service['Name'] . "\n");
                $this->delete( $service['ImageId'] );
            }
            */

            $client = $this->getClientEc2();

            try{
                
                $result = $client->createImage( $data );

                if( $this->waitToBeCreated(  $result['ImageId']  ) == false ){
                    $msg = "OK;EFS;002;91";
                    throw new Exception( $msg );
                }
                                    
            }catch (AwsException $e) {
                echo $e->getMessage();
                return false;
            }catch(Exception $e){
                echo $e->getMessage();
                return false;
            }
            
            
            return $result['ImageId'];


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


        public function getServiceById( $id ){
            if($id != true)
                return false;
            $client = $this->getClientEc2();
            try{
                $result = $client->describeImages([
                    'ImageIds' => [$id],
                ]);
            }catch( AwsException $e){
                return false;
            }
            if( !is_object($result ) || !is_array( $result ['Images'] ) || count( $result['Images'] ) != 1   )
                return false;

            $services = $result['Images'];

            return $services[0];
        }

        
        /**
         * Esta función devuelve todos los elementos de un servicio cloud con un nombre determinado.
         * El objeto VPC carece del parámetro VpcName, por lo que busca en los campos Tag 
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         */

        public function getServiceByName( $name  ){
            #echo "\n\$name = $name";
            if($name != true)
                return false;
            
            $client = $this->getClientEc2();
            
            $result = $client->describeImages([
                'Filters' => [
                    [
                        'Name' => 'name',
                        'Values' => [$name],
                    ],
                    // ...
                ],
            ]);
            
        
            if( !isset( $result ['Images'] ) || !is_array( $result ['Images'] )  )
                return false;

            $services = $result['Images'];

            return $services[0];

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
                $result = $client->describeImages([
                    'Filters' => [
                        [
                            'Name' => 'tag:Name',
                            'Values' => [$name],
                        ]
                    ] 
                ]);
        
            }catch( AwsException $e){
                
                return false;
            }

            if( !is_object($result ) || !is_array( $result ['Images'] ) || count( $result['Images'] ) != 1   )
                return false;

            $services = $result['Images'];

            return $services[0];


        }


        public function delete( $id ){
            if( !$this->exists($id) )
                return true;
            
            try{
                $client = $this->getClientEc2();
                $result = $client->deregisterImage([
                    'ImageId' => $id, // REQUIRED
                ]);

            }catch(AwsException $e){
                echo $e->getMessage();
                return false;

            }

            return $this->waitToBeDeleted( $id );


        }





        /**
         * @param string $name    Nombre de HostedZone
         * @param string $id      Una id de HostedZone. Usualmente comienza por "Z"
         * 
         *
         * @example 
         * 
         */


        public function exists( $id ){
            return $this->existsById( $id );
        }

        public function existsById( $id ){
            if( $id != true)
                return false;

            $service = $this->getServiceById( $id );
            if( $service == false)
                return false;

            return true;

        }


        public function existsByName( $name ){
            #echo "\$name = $name \n";
            if( $name != true)
                #echo "No es true";    
                return false;
            #echo "L464\n";

            $client = $this->getClientEc2();
            $result = $client->describeImages([
                'Filters' => [
                    [
                        'Name' => 'name',
                        'Values' => [$name],
                    ],
                    // ...
                ],

            ]);
            if(!isset( $result['Images'] )   )
                return false;

            $images = $result['Images'];

            if( count($images) == 1)
                return true;

            return false;

        }

        
        /**
         * @param string $vpcId         Una id de vpc. Usualmente comienza por "vpc-"
         * @param string $vpcTagName    valoer del tag "Name"
         *
         * @return boolean
         * 
         */

        public function isAvailableById( $id ){
            
            $service = $this->getServiceById( $id );
            if( isset( $service['State'] ) && strcmp( $service['State'], 'available' ) == 0 )
                return true;
            
            return false;
            
            /*    
            if( strcmp( $service['State'], 'pending' ) == 0 )
                return false;   

            if( strcmp( $service['State'], 'available' ) == 0 )
                return true;
                
            return false;
            */
        }

        /**
         * @param string $vpcId         Una id de vpc. Usualmente comienza por "vpc-"
         * @param string $vpcTagName    valoer del tag "Name"
         *
         * @return boolean
         * 
         */

        public function isAvailableByName( $name ){
            
            $service = $this->getServiceByName( $name );
            return $this->isAvailableById( $service['Name'] );



            #if( $service == false )
            #    return false;
            
            #return true;


            /*
            if( $service == false )
                return false;
            if( strcmp( $service['State'], 'pending' ) == 0 )
                return false;   

            if( strcmp( $service['State'], 'available' ) == 0 )
                return true;
                
            return false;
            */
        }


        /**
         * @param string    $name    valoer del tag "Name"
         * @param int       $seconds    Tiempo que duerme el itinerador
         * @param int       $times      Número máximo de comprobaciones
         * 
         */

        public function waitToBeCreated( $id, $seconds = null, $times = null ){
            if($seconds != true)
                $seconds = 30;
        
            if($times != true)
                $times = 30;
            
            $client = $this->getClientEc2();

            for($i = 0; $i < $times;  $i++, sleep( $seconds ) ) {
                
                $result = $client->describeImages([
                    'ImageIds' => [$id],
                ]);
                    
                $image = $result['Images'][0];

                if( $image['State'] == 'available' )
                    return true;

            }
            

            return false;
        }

        public function waitToBeDeleted( $id, $seconds = null, $times = null ) {

            if($seconds != true)
                $seconds = 30;
        
            if($times != true)
                $times = 10;
            

            $client = $this->getClientEc2();

            for($i = 0; $i < $times;  $i++, sleep( $seconds ) ) {
                    
                $result = $client->describeImages([
                    'ImageIds' => [$id],
                ]);
                if( !isset( $result['Images'][0]['InstanceId'] ) )    
                    return true;
    
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
            return $this->id;
        }

        public function getIdByName( $name ) {

            $service = $this->getServiceByName( $name );

            if( isset( $service[ 'ImageId' ] ) )
                return $service[ 'ImageId' ];
            
            return false;
        }

        public function setId( $id ) {
            $this->id = $id;
        }



        public function getStatus ($id ){
            #return true;

            $service = $this->getServiceById( $id );
            
            if ( isset( $service['State']['Name'] )  )
                return $service['State']['Name'];
            return false;
        
        }


        public function getStatusById ($id ){
            #return true;
            return $this->getStatus( $id );
        }


        public function getStatusByName ($name ){
            #return true;
            $service = $this->getServiceByName( $name );
            
            if ( isset( $service['State']['Name'] )  )
                return $service['State']['Name'];
            return false;



            #if ( $service == false)
            #    return false;

            #return $service['State'];

        }




    }

?>

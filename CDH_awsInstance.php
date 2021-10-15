<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsService.php';

    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsInstance extends CDH_abstractAwsService {
        
        
        

        


        public function __construct(){
            $this->setCredentials();
            $this->setClientEc2();
            $this->setClassName("CDH_awsInstance");
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
                
                $result     = $client->runInstances( $data );
                #$service    = $result['HostedZone'];
                $identifier = $result;

                if( $this->waitToBeCreated(  $identifier  ) == false )
                    throw new Exception( "L054 Demasiado tiempo esperando a la creación del servicio " . $this->getClassName() );

                return $result['Instances'] ;
            
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


        public function createOld( $data = [] ) {
            
            if( !isset ( $data['MaxCount'] ) || !isset ( $data['MinCount'] ) )
                return false;
            
            $client = $this->getClientEc2();
            
            try{
                
                $result = $client->runInstances( $data );
                echo PHP_EOL . "L429";

                #var_export( $result );
                echo PHP_EOL . "L434";
            }catch (AwsException $e) {
                echo PHP_EOL . "L436";
                echo $e->getMessage();
                return false;
            }catch(Exception $e){
                echo PHP_EOL . "L440";
                $e->getMessage();
                return false;
            }

            if( $this->waitToBeCreated(  $result  ) == false ){
                $msg = "KO;INSTANCE;002;448";
                echo $msg;
                return false;
            }    


            echo PHP_EOL . "L454";

            return $result['Instances'];


        }

        
        public function runInstance( $data ){
            return $this->create( $data );
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


        public function getServiceById( $id ){

            if($id != true)
                return false;

            $client = $this->getClientEc2();

            try{
                $result = $client->describeInstances([
                    'InstanceIds' => [$id],
                ]);
            }catch( AwsException $e){
                return false;
            }
            if( !isset($result ['Reservations'] ) || !is_array( $result ['Reservations'] )    )
                return false;


            $reservations = $result ['Reservations'];

            foreach ( $reservations as $reservation) {
                $instances = $reservation ['Instances'];
                foreach( $instances as $instance )
                    if( strcmp( $instance['InstanceId'], $id  ) == 0 )
                        return $instance;

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

        public function getServiceByName( $name  ){
            return $this->getServiceByTagName( $name ); 

            if($name != true)
                return false;

            $client = $this->getClientEc2();

            try{
                $result = $client->describeInstances([
                    'Filters' => [
                        [
                            'Name' => 'tag:Name',
                            'Values' => [$name],
                        ],
                     ],
                ]);

            }catch( AwsException $e){
                return false;
            }

            $reservations = $result ['Reservations'];

            foreach ( $reservations as $reservation) {
                $instances = $reservation ['Instances'];
                foreach( $instances as $instance )
                    if( strcmp( $instance['Tags'][0]['Value'], $name  ) == 0 )
                        return $instance;

            }

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
                $result = $client->describeInstances([
                    'Filters' => [
                        [
                            'Name' => 'tag:Name',
                            'Values' => [$name],
                        ],
                    ],
                ]);

            }catch( AwsException $e){
                return false;
            }

            $reservations = $result ['Reservations'];

            foreach ( $reservations as $reservation) {
                $instances = $reservation ['Instances'];
                foreach( $instances as $instance ){
                    $tags = $instance['Tags'];
                    foreach( $tags as $tag){
                        if( $tag['Key'] == 'Name' && $tag['Value'] == $name )
                            return $instance;
                    }

                }

            }

            return false;

        }



        public function terminateByTagName( $tagName ) {
            return $this->deleteByTagName(  $tagName );
        }

        public function deleteByTagName( $tagName ){
            $client = $this->getClientEc2();

            $result = $client->describeInstances([
                'Filters' => [
                    [
                        'Name' => 'tag:Name',
                        'Values' => [$tagName],
                    ],
                    // ...
                ],

            ]);


            if( !isset( $result['Reservations'][0]['Instances'] ) || !is_array( $result['Reservations'][0]['Instances'] ) ) {
                echo "\nL526 No hay Instancias para Borrar\n";
                return true;
            }
            
            
            $instances = $result['Reservations'][0]['Instances'];

            foreach( $instances as $instance ){
                $tags = $instance['Tags'];
                foreach( $tags as $tag ){
                    if( $tag['Key'] == 'Name' && $tag['Value'] == $tagName ) {
                        echo "\nL536 - \$instance['InstanceId'] = " . $instance['InstanceId'] . "\n";
                        $client->deleteTags([
                            'Resources' => [$instance['InstanceId']]
                        ]);
                        sleep(3);
                        $client->createTags([
                            'Resources' => [$instance['InstanceId']],
                            'Tags' => [ // REQUIRED
                                [
                                    'Key' => 'Name',
                                    'Value' => "For Removing - $tagName",
                                ],
                                // ...
                            ],
                        ]);
                        sleep(3);
                        try{
                            $result = $client->terminateInstances([
                                'InstanceIds' => [ $instance['InstanceId'] ], // REQUIRED
                            ]);    
                        }catch(AwsException $e){
                            die("\nL564 - Error terminateInstances \n");
                        }
                        return true;

                    }

                }

            }

            return true;
        }


        public function delete( $name ){

            if( !$this->exists($name) ){
                echo "\nL692 - Instancde $name  NO existe\n";
                return true;
            }
                
            $id = $this->getIdByName( $name );
            

            $client = $this->getClientEc2();

            try{
                $result = $client->terminateInstances([
                    'InstanceIds' => [$id], // REQUIRED
                ]);
                #return true;
            }catch(AwsException $e){
                $e->getMessage();
                return false;

            }

            return $this->waitToBeDeleted( $id );


        }

        public function exists( $name ){
            return $this->existsByName( $name );
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
            $client = $this->getClientEc2();

            $result = $client->describeInstances([
                'Filters' => [
                    [
                        'Name' => 'tag:Name',
                        'Values' => [$name],
                    ],
                    // ...
                ],

            ]);

            $reservations = $result ['Reservations'];

            foreach ( $reservations as $reservation) {
                $instances = $reservation ['Instances'];
                foreach( $instances as $instance ){
                    $tags = $instance['Tags'];
                    foreach( $tags as $tag){
                        #echo "\n\$name = $name | \$tag['Key'] = " . $tag['Key'] .  "\$tag['Value'] = " . $tag['Value']  . "\n";
                        if( $tag['Key'] == 'Name' && $tag['Value'] == $name )
                            return true;
                    }

                }

            }

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

            $status = $this->getStatusById( $id );
            
            if( strcmp( $status, 'available' ) == 0 )
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

        public function isAvailableByName( $name ){
            
            $service = $this->getServiceByName( $name );

            return $this->isAvailableByid( $service['InstanceId'] );

        }


        /**
         * @param string    $id         valor del id
         * @param int       $seconds    Tiempo que duerme el itinerador
         * @param int       $times      Número máximo de comprobaciones
         * 
         */

        public function waitToBeCreated( $result, $seconds = null, $times = null ){
            /*
            echo PHP_EOL;
            #var_export( $result );
            echo PHP_EOL;
            #var_export( $result['Instances'][0] );
            echo PHP_EOL;
            #var_export( $result['Instances'][0]['InstanceId'] );
            echo PHP_EOL;
            */

            #sleep (100);

            if($seconds != true)
                $seconds = 30;
        
            if($times != true)
                $times = 10;
            
            $instance = $result['Instances'][0];
            $id = $instance['InstanceId'];

            echo "\nL820 - awsImage.php \$id = $id";

            #$clientEc2 = $this->getClientEc2();

            for($i = 0; $i < $times;  $i++, sleep($seconds) ) {
                $status = $this->getStatusById( $id );
                echo PHP_EOL . "L825 - awsImage.php Instance \$status = $status";
                if( strcmp( $status, 'running' ) == 0 )
                    return true;
            }
            return false;
        }

        public function waitToBeDeleted( $id, $seconds = null, $times = null ){
            
            $client = $this->getClientEc2();

            $result = $client->deleteTags([
                'Resources' => [
                    $id,
                ],
            ]);
            
            sleep (5);
            #echo "\nL866 Changing Tags \$status = " . $status;    
            $result = $client->createTags([
                'Resources' => [
                    $id,
                ],
                'Tags' => [
                    [
                        'Key' => 'Name',
                        'Value' => 'DELETING_IMAGE_INSTANCE',
                    ],
                ],

            ]);    

            if($seconds != true)
                $seconds = 30;
    
            if($times != true)
                $times = 12;

            for($i = 0; $i < $times;  $i++ ){
                $status = $this->getStatusById( $id );
                echo "\nL841 \$status = " . $status;

                if( $status == 'terminated' || $status == false) 
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

            if($name != true)
                return false;

            $client = $this->getClientEc2();
            #die(  "\n\$name = $name"  );
            $result = $client->describeInstances([
                #'Filters' => [
                #    [
                #        'Name' => 'tag:Name',
                #        'Values' => [$name],
                #    ],
                #],
            ]);

            $reservations = $result ['Reservations'];


            foreach ( $reservations as $reservation) {
                $instances = $reservation ['Instances'];
                foreach( $instances as $instance ){
                    $tags = $instance['Tags'];
                    foreach( $tags as $tag){
                        #echo "\n\$name = $name | \$tag['Key'] = " . $tag['Key'] .  "\$tag['Value'] = " . $tag['Value']  . "\n";
                        if( $tag['Key'] == 'Name' && $tag['Value'] == $name )
                            return $instance['InstanceId'];
                    }

                }

            }

            return false;

        }

        public function setId( $id ) {
            $this->id = $id;
        }


        public function getStatus ($id ){

            $service = $this->getServiceById( $id );
        
            if ( isset( $service['State']['Name'] )  )
                return $service['State']['Name'];
            
            return false;
        
        }


        public function getStatusById ($id ){
            return $this->getStatus( $id );
        }


        public function getStatusByName ($name ){
            
            $service = $this->getServiceByName( $name );
            if ( $service != false )
                return $service['State']['Name'];

            return false;

        }




    }

?>

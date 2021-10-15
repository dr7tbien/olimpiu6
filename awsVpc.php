<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_PS_abstractAwsService.php';
    require_once 'defines/services_aws/CDH_PS_defineAwsVpc.php';
    require_once 'vendor/autoload.php';

    use Aws\Exception\AwsException;

    
    class awsVpc extends CDH_PS_abstractAwsService {
        
        
        private $fields = [
            'CidrBlock',                    #string 
            'CidrBlockAssociationSet',      #array
            'DhcpOptionsId',                #string
            'InstanceTenancy',              #string: default|dedicated|host
            'Ipv6CidrBlockAssociationSet',  #array
            'IsDefault',                    #boolean
            'OwnerId',                      #string
            'State',                        #string: pending|available
            'Tags',                         #array
            'VpcId'                         #[vpc-]+string
        ];
        


        public function __construct(){
            $this->setCredentials();
            $this->setClientEc2();
        }
        
        public function create() {
            $client = $this->getClientEc2();

            #$vpc = $this->getServiceByName( VPC_NAME );

            #if ($this->exists( $vpc[ 'VpcId' ] ))         
            #    return false;
            
            try{
                #validar existencia vpc
                $validarVpcExiste = $this->exists(VPC_TAGS_VALUE);

                
                /*if ($validarVpcExiste)
                    throw new AwsException('Vpc existe',$interface);*/

                $resultVpc = $client->createVpc ([
                    'AmazonProvidedIpv6CidrBlock'   => VPC_AMAZONPROVIDEDIPV6CIDRBLOCK,
                    'CidrBlock'                     => VPC_CIDRBLOCK,
                    'DryRun'                        => VPC_DRYRUN,
                    'InstanceTenancy'               => VPC_INSTANCETENANCY,
                    'PrivateDnsEnabled'             => true,
                ]);
                    
                $this->setCloudObject($resultVpc['Vpc']);
                $this->setId($resultVpc['Vpc']['VpcId']); 


            }catch (AwsException $e) {
                echo "Vpc ya existe" . PHP_EOL;
                /*echo $e->getMessage();
                die();*/


                $resultVpcs = $client->describeVpcs([
                    'Filters' => [
                        [
                            'Name' => 'tag:Name',
                            'Values' => [VPC_NAME],
                        ],
                    ]
                ]);

                $this->setCloudObject( $resultVpcs['Vpcs'][0] );
                $this->setId( $resultVpcs['Vpcs'][0]['VpcId'] );
                return true;
            }

            //$vpcId = $resultVpc['Vpc']['VpcId'];


            #$climate->out( "\$vpcId = $vpcId" );
            #$input = $climate->input('How you doin?');
            #$response = $input->prompt();

            try{
                $client->modifyVpcAttribute([
                    'EnableDnsHostnames' => [
                        'Value' => true,
                    ],
                    'VpcId' => $this->getId(),
                ]);
            }catch (AwsException $e) { //Suponemos que si no se crea es porque ya existe
                echo $e->getMessage();
                return false;
            }
    
            try{
                $client->createTags ([
                    'Resources' =>  [$this->getId()],
                    'Tags'      =>  [
                            [
                                'Key'   => VPC_TAGS_KEY,
                                'Value' => VPC_TAGS_VALUE
                            ]
                        ]
                ]);
            }catch (AwsException $e) {
                echo $e->getMessage();
                return false;
            }
               
            $this->setCloudObject( $this->getServiceById( $this->getId() ) );

            /**
             * // -- crear mensajes de exito error clase error crear
             */

        }

        /**
         * Esta función busca el valor de un campo del array. Existen campos con igual nombre
         * pero en distinto nivel del array. si el campo buscado está en el primer nivel o es único
         * la función trabaja bien.
         * 
         * En los casos en que el campo esté repetido, pero en distinto nivel, es necesario insertar 
         * el argumento $cloudObject como un array interno con campos únicos   
         * 
         * @param string $field cualquier valor contenido en el VPC
         *                  
         * 'CidrBlock'                      (cadena)
         * 'CidrBlockAssociationSet'        (array)           
         * 'DhcpOptionsId'                  (cadena)
         * 'InstanceTenancy'                (cadena: default|dedicated|host)
         * 'Ipv6CidrBlockAssociationSet'    (array)
         * 'IsDefault'                      (boolean)
         * 'OwnerId'                        (cadena)
         * 'State'                          (cadena: pending|available)
         * 'Tags'                           (array)
         * 'VpcId'                          (cadena)
         * 
         * @param string $vpcId
         * 
         * @return string|array|boolean
         * 
         * */


        public function getFieldValueById ( $field, $vpcId ) {
            $fields = $this->getFields();

            for( $i=0; $i < count( $fields ); $i++ ) {
                if( strcasecmp( $fields[$i], $field ) == 0 )
                    $field = $fields[$i];
            }

            $clientEc2 = $this->getClientEc2();
            $result = $clientEc2->describeVpcs([
                #'DryRun' => true || false,
                #'Filters' => [
                #    [
                #        'Name' => '<string>',
                #        'Values' => ['<string>', ...],
                #    ],
                    // ...
                #],
                #'MaxR esults' => <integer>,
                #'NextToken' => '<string>',
                'VpcIds' => [$VpcId],
            ]); 

            $vpcs = $result->search('Vpcs');

            if ( count( $vpcs ) != 1)
                return false;

            $vpc = $vpcs[0];

            return $vpc[ $field ];

        }




        /**
         * Esta función busca el valor de un campo del array. Existen campos con igual nombre
         * pero en distinto nivel del array. si el campo buscado está en el primer nivel o es único
         * la función trabaja bien.
         * 
         * En los casos en que el campo esté repetido, pero en distinto nivel, es necesario insertar 
         * el argumento $cloudObject como un array interno con campos únicos   
         * 
         * @param string $field cualquier valor contenido en el VPC
         *                  
         * 'CidrBlock'                      (cadena)
         * 'CidrBlockAssociationSet'        (array)           
         * 'DhcpOptionsId'                  (cadena)
         * 'InstanceTenancy'                (cadena: default|dedicated|host)
         * 'Ipv6CidrBlockAssociationSet'    (array)
         * 'IsDefault'                      (boolean)
         * 'OwnerId'                        (cadena)
         * 'State'                          (cadena: pending|available)
         * 'Tags'                           (array)
         * 'VpcId'                          (cadena)
         * 
         * @param string $vpcName
         * 
         * @return string|array|boolean
         * 
         * */

        public function getFieldValueByName ( $field, $vpcName ){

            $fields = $this->getFields();

            for( $i=0; $i < count( $fields ); $i++ ) {
                if( strcasecmp( $fields[$i], $field ) == 0 )
                    $field = $fields[$i];
            }

            $clientEc2 = $this->getClientEc2();
            $result = $clientEc2->describeVpcs([
                'Filters' => [
                    [
                        'Name' => 'tag:Name',
                        'Values' => [$vpcName],
                    ],
                        // ...
                ],
            ]); 

            $vpcs = $result->search('Vpcs');

            if ( count( $vpcs ) != 1)
                return false;

            $vpc = $vpcs[0];

            return $vpc[ $field ];

        }

        public function getTagValue( $tagKey, $vpcId = false, $vpcName = false ) {
            if( $vpcId == false && $vpcName == false){ //Se trata del vpc creado con el mismo objeto
                $vpc = $this->getCloudObject();
            }
            
            if( $vpcId == false && $vpcName == true){
                $vpc = $this->getVpcByName( $vpcName );                
                $vpcTags = $vpc['Tags'];
            }


            if( $vpcId == true && $vpcName == false ){ //Si $id == $name == true siemre se busca por $id
                $vpc = $this->getVpcById( $vpcId );
                $vpcTags = $vpc['Tags'];
            }
            
            if( $vpcId == true && $vpcName == true){
                $vpc    = $this->getVpcByName ( $vpcName  );
                $vpc0   = $this->getVpcById   ( $vpcId    );
                if (array_diff($vpc,$vpc0) != array_diff($vpc0,$vpc)) 
                    return false;
            }

            $vpcTags = $vpc['Tags'];
                foreach( $vpcTags as $tag  ) 
                    if( $tag['Key'] == $tagKey )
                        return $tag['Value'];


            return false;



        }

        /**
         * Esta función devuelve todos los elementos de un servicio cloud de una id determinada
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         */


        public function getServiceById( $id ){
            try{
                $client = $this->getClientEc2();
                $result = $client->describeVpcs([ 'VpcIds' => [ $id ] ]); 
            }catch ( AwsExceptin $e ) {
                return false;
            }

            if( is_array( $result['Vpcs'] )  && count( $result['Vpcs'] ) == 1 )
                return $result['Vpcs'][0];

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
            try{
                $clientEc2 = $this->getClientEc2();
                $result = $clientEc2->describeVpcs([
                    'Filters' => [
                        [
                            'Name' => 'tag:Name',
                            'Values' => [$name],
                        ],
                            // ...
                    ],
                ]);
            } catch ( AwsExceptin $e ) {
                return false;
            }

            if( is_array( $result['Vpcs'] )  && count( $result['Vpcs'] ) == 1 )
                return $result['Vpcs'][0];
                
            return false;
        }

         /**
         * Esta función devuelve todos los elementos de un servicio cloud con un nombre determinado.
         * El objeto VPC carece del parámetro VpcName, por lo que busca en los campos Tag.
         * Esta función es idéntica a getServiceByName y se ha implementado por tema de compatibilidad
         * 
         * @param string $id [vpc-]...
         * @return array Objeto de AWS
         */

        public function getServiceByTagName( $name = null ){
            return $this->getServiceByName( $name );
        }


        public function delete( $name = null){
            $id = $this->getId( $name );            
            $client = $this->getClientEc2();
            $result = $client->deleteVpc([
                #'DryRun' => true || false,
                'VpcId' => $id, // REQUIRED
            ]);

        }

        /**
         * @param string $vpcId         Una id de vpc. Usualmente comienza por "vpc-"
         * @param string $vpcTagName    valoer del tag "Name"
         *
         * @example 
         * <code>
         * <?php
         *  
         *  OK - In this sentence the function search matching param $vpcId
         *  $object->exists( $vpcId  = "vpc-xxxxxxxx", $vpcTagName = false ){ ... }
         *   
         *  OK - In this sentence the function search matching param $vpcTagName
         *  $object->exists( $vpcId  = false, $vpcTagName = "VPC_NAME" ){ ... }
         * 
         *  OK - In this sentence the function search matching both params
         *  $object->exists( $vpcId  = "vpc-xxxxxxxx", $vpcTagName = "VPC_NAME" ){ ... }
         *
         * ?>
         * </code>
         * 
         */


        public function exists($vpcTagName = false, $vpcId  = false){
            if( $vpcId == false && $vpcTagName == false )
                return false;

            $clientEc2 = $this->getClientEc2();
            try{

                if( $vpcId == true && $vpcTagName == true ){
                    $result = $clientEc2->describeVpcs([
                        'VpcIds' => [$VpcId],
                        'Filters' => [
                            [
                                'Name' => 'tag:Name',
                                'Values' => [$vpcTagName],
                            ],
                                // ...
                        ],
                    ]);
        
                    if(count( $result['Vpcs'] ) == 0 )
                        return false;
            
                    return true;
                }        
                

                if( $vpcId == true ){
                    $result = $clientEc2->describeVpcs([
                        'VpcIds' => [$VpcId],
                    ]);
        
                    if(count( $result['Vpcs'] ) == 0 )
                        return false;
            
                    return true;
                }

                if( $vpcTagName == true ){
                    $result = $clientEc2->describeVpcs([
                        'Filters' => [
                            [
                                'Name' => 'tag:Name',
                                'Values' => [$vpcTagName],
                            ],
                                // ...
                        ],
                    ]);
        
                    if(count( $result['Vpcs'] ) == 0 )
                        return false;            
                    return true;
                }

            }catch ( AwsExceptin $e ) {
                return false;
            }

        }

        
        /**
         * @param string $vpcId         Una id de vpc. Usualmente comienza por "vpc-"
         * @param string $vpcTagName    valoer del tag "Name"
         *
         * @return boolean
         * 
         */

        public function isAvailable( $vpcId  = false, $vpcTagName = false ){
            if( $vpcId == false && $vpcTagName == false )
                return false;

            try{

                if( ($vpcId == true && $vpcTagName == true) || $vpcTagName == true ){
                    $result = $clientEc2->describeVpcs([
                        'VpcIds' => [$VpcId],
                        'Filters' => [
                            [
                                'Name' => 'tag:Name',
                                'Values' => [$vpcTagName],
                            ],
                                // ...
                        ],
                    ]);
        

                }        
                

                if( $vpcId == true && $vpcTagName == false){
                    $result = $clientEc2->describeVpcs([
                        'VpcIds' => [$VpcId],
                    ]);
                }

                if( (count( $result['Vpcs'] ) == 1) && $result['Vpcs'][0]['State'] == 'available' )
                    return true;
    
                return false;

            }catch ( AwsExceptin $e ) {
                return false;
            }

        }


        /**
         * @param string    $vpcName    valoer del tag "Name"
         * @param int       $sleeper    Tiempo que duerme el itinerador
         * @param int       $times      Número máximo de comprobaciones
         * 
         */

        public function waitToBeCreated( $vpcName, $sleeper = false, $times = false){
            $time = 0; 
            if ($sleeper == false)
                $sleeper = 5;

            if ($times == false)
                $times = 5;

            while (  $this->isAvailable( false, $vpcName ) == false && $time < $times  ) {
                sleep( $sleeper );
                $time++;
            } 
                
        }

        public function getId( $vpcTagName = false ) {
            if ( $vpcTagName == false )
                return $this->id;
            
            $vpc = $this->getServiceByName( $vpcTagName );
            return $vpc[ 'VpcId' ];
        }

        public function setId( $id ) {
            $this->id = $id;
        }

        public function getFields(){
            return $this->fields;
        }

    }

?>

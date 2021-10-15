<?php
    require_once 'CDH_PS_abstractAwsService.php';
    include_once 'defines/services_aws/CDH_PS_awsDefineVpc.php';
    include_once 'defines/services_aws/CDH_PS_awsDefineHostedZone.php';

    include_once 'classes/awsVpc.php';

    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    
    class awsHostedZone extends CDH_PS_abstractAwsService {
        

    
        public function __construct(){
            $this->setCredentials();
            $this->setClientR53();
            $this->setClientEc2();
        }
        
        public function create() {
        
            $clientR53 = $this->getClientR53();
            $clientEc2 = $this->getClientEc2();



            /*
            $climate->out("\$callerReference = $callerReference");
            $climate->out("\$comment = $comment");
            $climate->out("\$name = $name");
            $climate->out("\$VPCId = $VPCId");
            $climate->out("\$VpcRegion = $VpcRegion");
            $input = $climate->input('How you doin?');
            $response = $input->prompt();
            */


            try{
                $resultR53 = $clientR53->createHostedZone([
                    'CallerReference' => time(), // REQUIRED
                    #'DelegationSetId' => '<string>',
                    'HostedZoneConfig' => [
                        'Comment' => HZ_HostedZoneConfig_Comment,
                        'PrivateZone' => false,
                    ],
                    'Name' => DOMINIO_CON_PUNTO, // REQUIRED
                    'VPC' => [
                        'VPCId' => $vpc->getId( VPC_NAME ),
                        'VPCRegion' => VPC_REGION,
                    ],
                ]);

                $this->setId( $resultR53['HostedZone']['Id']  );
                $this->setCloudObject( $resultR53['HostedZone'] );
                sleep(5);

                $result = $clientR53->changeTagsForResource([
                    'AddTags' => [
                        [
                            'Key' => HZ_TAGS_Name,
                            'Value' => HZ_TAGS_Value,
                        ],
                        // ...
                    ],
                    #'RemoveTagKeys' => ['<string>', ...],
                    'ResourceId' => str_replace('/hostedzone/', '', $resultR53['HostedZone']['Id'] ),    // REQUIRED
                    'ResourceType' => 'hostedzone', // REQUIRED
                ]);
                
                return true;            

            }catch (AwsException $e) { //HostedZone Repetido
                $result = $clientR53->listHostedZones([]);
                $hzs = $result->search( 'HostedZones' );
                foreach ( $hzs as $hz ){
                    if ( $hz['Name'] == DOMINIO_CON_PUNTO ) {
                        $this->setId( $hz['Id']  );
                        $this->setCloudObject( $hz );
                    }
                }
            }



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
         *                  - CidrBlock
         *                  - AssociationId
         *                  -- CidrBlock
         *                  -- State
         *                  -- StatusMessage
         *                  - DhcpOptionsId
         *                  - InstanceTenancy
         *                  - AssociationId
         *                  - Ipv6CidrBlock
         *                  -- State
         *                  - StatusMessage
         *                  - Ipv6Pool
         *                  - NetworkBorderGroup
         *                  - IsDefault'
         *                  - OwnerId'
         *                  -- State'
         *                  - VpcId
         * 
         * @param array $cloudObject array contenedor del VPC. Si @field no es único hay
         *                  que insertar el array interno sin elementos repetidos
         *              si no existe la función busca en $this->cloudObject
         *              La función sólo busca en el primer nivel de array
         * */


        public function getFieldValue ( $field = null, $cloudObject = false ) {

            if( $cloudObject == false )
                $cloudObject = $this->getCloudObject();

            if ( !is_array ( $cloudObject ) )
                return false;
        
            return $this->getDataFromArray( $field, $cloudObject );

        }

        public function getFieldValueById ( $field = null, $hz = null  ){
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
                #'MaxResults' => <integer>,
                #'NextToken' => '<string>',
                'VpcIds' => [$VpcId],
            ]);  

            if( count ( $result [ 'Vpcs' ] ) != 1  )
                return false;

            return $this->getDataFromArray( $field, $result [ 'Vpcs' ][0] );

            
        }

        public function getFieldValueByName ( $field = null, $Name = null, $arrayNested = null ){
            $clientEc2 = $this->getClientEc2();
            $result = $clientEc2->describeVpcs([
                'Filters' => [
                    [
                        'Name' => 'tag:Name',
                        'Values' => [$Name],
                    ],
                        // ...
                ],
            ]);

            if( count ( $result [ 'Vpcs' ] ) != 1  )
                return false;

            if ( $arrayNested == false )        
                return $this->getDataFromArray( $field, $result [ 'Vpcs' ][0] );

            if ( is_array( $result ['Vpcs' ][0][ $arrayNested ] ) )
                return $this->getDataFromArray( $field, $result ['Vpcs' ][0][ $arrayNested ] );
            
            return false;

        }

        public function getTagValue( $tagName = null, $id = false, $name = false ) {
            if( $id == false && $name == false){ //Se trata del vpc creado con el mismo objeto
                $vpc = $this->getCloudObject();
                $vpcTags = $vpc['Tags'];
                foreach( $vpcTags as $tag  ) 
                    if( $tag['Name'] == $tagName )
                        return $tag['Value'];
            }
            
            if( $id == true ){ //Si $id == $name == true siemre se busca por $id
                $vpc = $this->getVpcById( $id );
                $vpcTags = $vpc['Tags'];
                foreach( $vpcTags as $tag  ) 
                    if( $tag['Name'] == $tagName )
                        return $tag['Value'];

            }
            
            if( $id == false && $name == true){
                $vpc = $this->getVpcByName( $name );                
                $vpcTags = $vpc['Tags'];
                foreach( $vpcTags as $tag  ) 
                    if( $tag['Name'] == $tagName )
                        return $tag['Value'];

            }

            return false;



        }

        public function getServiceById( $id = null ){
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

        public function getServiceByName( $name = null ){
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

        public function delete($id = null){
            $result = $client->deleteVpc([
                'DryRun' => true || false,
                'VpcId' => '<string>', // REQUIRED
            ]);
        }

        public function exists( $id = null ){
            try{
                $client = $this->getClientEc2();
                $result = $client->describeVpcs([ 'VpcIds' => [ $id ] ]); 
            }catch ( AwsExceptin $e ) {
                return false;
            }

            if( is_array( $result['Vpcs'] )  && count( $result['Vpcs'] ) == 1 ) 
                return true;
                
            return false;

        }

        public function isAvailable( $id = null ){
            try{
                $client = $this->getClientEc2();
                $result = $client->describeVpcs([ 'VpcIds' => [ $id ] ]); 
            }catch ( AwsExceptin $e ) {
                return false;
            }

            if( is_array( $result['Vpcs'] )  && $result['Vpcs'][0]['State'] == 'available'  ) 
                return true;
                
            return false;

        }


        public function waitForTheSystemToBuildTheService( $vpcId = null, $sleeper = null, $times = null){

        }

        public function getId( $name=false ){
            if($name == false)
                return $id;


            $clientR53  = $this->getClientR53();
            $result     = $clientR53->listHostedZones([]);
            $hzs        = $result->search('HostedZones');

            #print_r( $result );

            #print_r( $hzs );

            foreach ( $hzs as $hz ) {
                $patron = DOMINIO_SIN_PUNTO;  
                if( preg_match( "/^$patron/i", $hz['Name']  )  )  #$hz['Name'] == DOMINIO_CON_PUNTO ) 
                    return str_replace('/hostedzone/', '', $hz['Id'] );
            }

            return false;

        }


        public function getIid( $vpcTagName = false ) {
            if ( $vpcTagName == false )
                return $this->id;
            
            $vpc = $this->getServiceByName( $vpcTagName );
            return $vpc[ 'VpcId' ];
        }




    }

?>

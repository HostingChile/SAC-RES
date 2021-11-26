<?php
require __DIR__ . '/ConnectionManager.class.php';
require __DIR__ . '/Deal.class.php';

class ClientsManager {
    
    private $connections_data, $dns_data, $conn_manager;
    
    public function __construct($hosting_data){
        //Filter data
        $this->connections_data = array_map(function($v){
            return $v['connection'];
        }, $hosting_data);

        $this->dns_data = array_map(function($v){
            return $v['dns'];
        }, $hosting_data);

        $this->conn_manager = new ConnectionManager($this->connections_data);                
    }
    
    private function check_params($expected_params, $received_params){
        foreach($expected_params as $expected_param){
            if(!array_key_exists($expected_param, $received_params) || !isset($received_params[$expected_param])){
                throw new Exception('Some parameters are missing: ['.implode(',', array_diff($expected_params, array_keys($received_params))).']');
            }
        }
        
        return true;
    }

    public function get_all($params) {
        $expected_params = array('start', 'end');
        $this->check_params($expected_params, $params);      
        $oportunidad = new Deal();
        $resultado = $oportunidad->get_all_deals();
        $start = $params['start'];
        $end = $params['end'];
        
        $query = '  SELECT *, DATE_FORMAT(FECHA,"%d-%m-%Y") as FECHA_FORMATEADA 
                    FROM CLIENTE 
                    WHERE FECHA >= DATE(NOW()) - INTERVAL :start DAY 
                    AND FECHA <= DATE(NOW()) - INTERVAL :end DAY
                    AND DOMINIO NOT LIKE "%.pruebadehosting.com"
                    ORDER BY FECHA ASC';

        $response = array();
        foreach (array_keys($this->connections_data) as $hosting) {
            $result = $this->conn_manager->query($hosting, $query, array('start' => $start, 'end' => $end));

            while ($row = $result->fetch()) {
                $dominio = $row['DOMINIO'];
                $dns_entries = dns_get_record($dominio, DNS_NS);
                $dns = array();
                foreach ($dns_entries as $dns_entry) {
                    $dns[] = $dns_entry['target'];
                }

                sort($dns);
                
                $dns_status = 'danger';
                foreach($this->dns_data[$hosting] as $dns_set){
                    sort($dns_set);
                    $inter = count(array_intersect(array_unique($dns), $dns_set));
                    if($inter == count($dns_set)){
                        $dns_status = 'success';
                        break;
                    }
                    elseif($inter > 0){
                        $dns_status = 'warning';
                    }
                }
               
                $exist_deal = false;
                if(!$resultado == $exist_deal)
                {
                    foreach ($resultado['data'] as $deal) {
                    if($deal['e362fc51256cb4421f2842a3f6880ca88d90c8a4'] == $row['ID_CLIENTE']){
                        $exist_deal = true;
                        }
                    }
                }

                   
                $inter = count(array_intersect(array_unique($dns), $this->dns_data[$hosting]));
                $response['msg'][] = array(
                    'id' => $row['ID_CLIENTE'],
                    'dominio' => $dominio,
                    'nombre' => $row['NOMBRE'],
                    'fono' => $row['FONO'],
                    'cel' => $row['CEL'] == '' ? '-' : $row['CEL'],
                    'fecha' => $row['FECHA_FORMATEADA'],
                    'dns' => $dns,
                    'dns_status' => $dns_status,
                    'empresa' => $hosting,
                    'contactado' => strtolower($row['CONTACTADO']),
                    'contactado_por' => $row['CONTACTADO_POR'],
                    'comentario' => $row['COMENTARIO'],
                    'exist_deal' => $exist_deal
                );
            }
        }
        $response['success'] = true;
        
        return $response;
    }

    public function change_contacted($params) {
        $expected_params = array('contacted', 'id', 'hosting');
        $this->check_params($expected_params, $params);
        
        $contacted = $params['contacted'] == "true" ? 'Si' : 'No';
        $id = $params['id'];
        $hosting = $params['hosting'];
        
        $query = '  UPDATE CLIENTE 
                    SET CONTACTADO = :contacted, 
                    CONTACTADO_POR = :user 
                    WHERE ID_CLIENTE = :id';
        $result = $this->conn_manager->query($hosting, $query, array(
            'contacted' => $contacted,
            'user' => $contacted == 'Si' ? $_SESSION['user_name'] : '',
            'id' => $id,
        ));

        return $result->rowCount() ? array('success' => true, 'msg' => $_SESSION['user_name']) : array('success' => false, 'msg' => "No changes detected");
    }
    
    public function edit_comment($params){
        $expected_params = array('comment', 'id', 'hosting');
        $this->check_params($expected_params, $params);
        
        $comment = $params['comment'];
        $id = $params['id'];
        $hosting = $params['hosting'];
        
        $query = '  UPDATE CLIENTE 
                    SET COMENTARIO = :comment
                    WHERE ID_CLIENTE = :id';
        $result = $this->conn_manager->query($hosting, $query, array(
            'comment' => $comment,
            'id' => $id,
        ));

        return array('success' => $result->rowCount(), 'msg' => "No changes detected");
    }

    public function create_deal($params){
        $expected_params = array('id', 'hosting');
        $this->check_params($expected_params, $params);
        $id = $params['id'];
        $hosting = $params['hosting'];
        
        $query = '  SELECT MONTO, NOMBRE, FONO, CEL, EMAIL, NOMBRE_PLAN, DOMINIO FROM CLIENTE 
                    WHERE ID_CLIENTE = :id';
        $result = $this->conn_manager->query($hosting, $query, array(
            'id' => $id
        ));
        $row = $result->fetch();

       $person = array(
            "name" => $row['NOMBRE'],
            "phone" => $row['FONO'],
            "170efe90b740123601457cc3c8905b6d31f9ab02" => $row['FONO'],
            "9f818bcd9688be73694c989aa0efb33f3698022d" => $row['CEL'],
            "email" => $row['EMAIL']
        );

        $oportunidad = new Deal();

        $result_p = $oportunidad->create($person,"persons");
        if (!$result_p){
           return array('success' => false, 'msg' => "Error en la API (Person) CRM, no se creo negocio");
        }
        
       $deal = array(
            "title" => "Servicio Nuevo sin Pago - Plan ".strtoupper($row['NOMBRE_PLAN']) ,
            "value" => $row['MONTO'],
            "status" => "open",
            "visible_to" => "1",
            "person_id" => $result_p['data']['id'],
            "0eaa84c11c47308a4b71fd79fce2190dffc15a20" => "Servicios Nuevos sin Pago (SAC)", 
            "80ae21bb2517c22cf8f55231ae43a87cd4d38785" => $row['DOMINIO'],
            "e362fc51256cb4421f2842a3f6880ca88d90c8a4" => $id
        ); 
            $result_d = $oportunidad->create($deal,"deals");
            if(!$result_d){
                $oportunidad->create("persons/{".$result_p['data']['id']."}");
                return array('success' => false, 'msg' => "Error en la API (Deal) CRM, no se creo negocio");
            }
            
           return array('success' => true, 'msg' => "Se ha creado oportunidad en CRM, correctamente!!");

    }

}

<?php

/**
 * Namesilo DDNS
 * Author : Panda Tom 
 * Date : 2018/12/15
 * Usage : 
 * 			add crontab command (every 10 minutes)
 *         php /www/wwwroot/ddns_namesilo.php
 */
class namesilo
{
	private $apiKey;
	private $domain;
	private $rrhost;
	
	function __construct()
	{
		$this->apiKey = 'c8082318267c813f91b36111'; // set your namesilo api key
		$this->domain = 'ssrpanel.com'; // set your domain
		$this->rrhost = 'hk'; // set your sub domain
	}
	
	function ddns()
	{
		$ip = $this->checkIP();
		if (false === $ip) {
			exit('IP NOT CHANGED');
		}
		
		$recordList = $this->dnsListRecords();
		
		$rrid = '';
		foreach ($recordList['reply']['resource_record'] as $vo) {
			if ($vo['host'] == $this->rrhost . '.' . $this->domain) {
				$rrid = $vo['record_id'];
				break;
			}
		}
		
		$this->log($rrid);
		
		if ($rrid) {
			$this->dnsUpdateRecord($rrid, $this->rrhost, $ip);
		}
		
		exit();
	}
	
    function dnsListRecords()
    {
        $query = [
            'domain' => $this->domain
        ];
		
        return $this->send('dnsListRecords', $query);
    }
	
    function dnsUpdateRecord($rrid, $rrhost, $rrvalue, $ttl = 7207)
    {
        $query = [
            'domain'  => $this->domain,
            'rrid'    => $rrid,
            'rrhost'  => $rrhost,
            'rrvalue' => $rrvalue,
            'rrttl'   => $ttl
        ];
		
        return $this->send('dnsUpdateRecord', $query);
    }
	
	function getIP()
	{
		$result = $this->curl('http://ip.taobao.com/service/getIpInfo.php?ip=myip');
		$result = json_decode($result, true);
		
		return $result['data']['ip'];
	}

	function checkIP()
	{
		$nowIP = $this->getIP();
		
		$file = dirname(__FILE__) . '/ddns_namesilo.ip';
		$oldIP = file_get_contents($file);
		
		if ($nowIP == $oldIP) {
			return false;
		}
		
		file_put_contents($file, $nowIP);
		
		return $nowIP;
	}
	
    function send($operation, $data = [])
    {
        $params = [
            'version' => 1,
            'type'    => 'xml',
            'key'     => $this->apiKey
        ];
        $query = array_merge($params, $data);
		
		$result = $this->curl('https://www.namesilo.com/api/' . $operation . '?' . http_build_query($query));
		$result = $this->xmlToArray($result);
		$this->log($result);
		
		return $result;
    }
	
	function log($data)
	{
		$file = dirname(__FILE__) . '/ddns_namesilo.log';
		file_put_contents($file, var_export($data, true), FILE_APPEND);
	}
	
	function xmlToArray($xml)
	{
		libxml_disable_entity_loader(true);
        $result = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        
		return $result;
	}
	
	function curl($url, $data = [])
	{
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 500);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_URL, $url);
		
        if ($data) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
		
        $result = curl_exec($ch);
        curl_close($ch);
		
        return $result;
	}
}

$namesilo = new namesilo();
$dnsListRecords = $namesilo->ddns();

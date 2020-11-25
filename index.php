<?php

// JSON pretty print, use parameter 'pretty' to set it
$json_pretty_print = $_GET['pretty'] ? JSON_PRETTY_PRINT : false; 
// Limit objects, use parameter 'limit' to set it
$objects_limit = $_GET['limit'] ? $_GET['limit'] : INF;

$xml_dir_path = './annotations';
$file_names = scandir($xml_dir_path);
$file_names = array_diff($file_names, ['.', '..']); // Remove . and .. from array

natsort($file_names); // Sort order by alphanumeric

foreach ($file_names as $file_name) {
    $file_path = $xml_dir_path . '/' . $file_name;
    $xml = file_get_contents($file_path);
    $array = xml_to_array($xml);

    $image_width = $array['size']['width'];
    $image_height = $array['size']['height'];
    $objects = [];

    if(count($array['object'][0])) {
        foreach ($array['object'] as $i => $object_item) {
            if($i < $objects_limit) {
                $objects[] = get_filtered_object_item($object_item, $image_width, $image_height);
            }
        }
    } else {
        // If data is only one
        $object_item = $array['object'];
        $objects[] = get_filtered_object_item($object_item, $image_width, $image_height);
    }

    $filtered_array = ['objects' => $objects,];

    echo '<pre>';
    echo '<strong>' . $file_path . '</strong>';
    echo '<br/>';
    echo json_encode($filtered_array, $json_pretty_print);
    echo '<hr/>';
    echo '</pre>';
}

function xml_to_array($xml) {
    $xml = simplexml_load_string($xml);
    $json = json_encode($xml);
    $array = json_decode($json,TRUE);

    return $array;
}

function fix_max_axis($max_axis, $fixed_max_axis) {
    return ($max_axis > $fixed_max_axis) ? $fixed_max_axis : $max_axis;
}

function get_filtered_object_item($object_item, $image_width, $image_height) {
    $filtered_object_item = [
        'location' => [
            'left' => (int) $object_item['bndbox']['xmin'],
            'top' => (int) $object_item['bndbox']['ymin'],
            'width' => (int) fix_max_axis($object_item['bndbox']['xmax'], $image_width) - $object_item['bndbox']['xmin'],
            'height' => (int) fix_max_axis($object_item['bndbox']['ymax'], $image_height) - $object_item['bndbox']['ymin'],
        ],
        'object' => $object_item['name'],
    ];

    return $filtered_object_item;
}

?>
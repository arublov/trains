
<?php

//include the file that loads the PhpSpreadsheet classes
require 'spreadsheet/vendor/autoload.php';

//include the classes needed to create and write .xlsx file
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function checkT($d, $default){

    if($d > $default) $data = '+';
    else $data = '-';

    return $data;

}

?>

<link rel="stylesheet" href="css/chartist.min.css">

<script src="https://code.jquery.com/jquery-3.3.1.min.js"
        integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
        crossorigin="anonymous"></script>
<script src="//cdn.jsdelivr.net/chartist.js/latest/chartist.min.js"></script>
<script src="js/chartist-plugin-zoom.js"></script>

<form method="post" enctype="multipart/form-data">
    <input type="file" name="file">
    <input type="submit" name="submit" value="Send">
</form>
<?php

if($_POST){

    $uploadfile = 'uploads/' . basename($_FILES['file']['name']);
    $valid = false;
    $outputfile = rand();

    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {

        $types = array('Xls', 'Xlsx');
        foreach ($types as $type) {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($type);
            if ($reader->canRead($uploadfile)) {
                $valid = true;
            }
        }

        if ($valid) {

            echo "<span style='color: green;'>Success upload : {$_FILES['file']['name']}</span>";
            echo "<span style='color: green;'> | Success create <a href='o/$outputfile.xlsx'>download file</a></span>";

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($uploadfile);

            $xls_data = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            for($i = 2; $i <= count($xls_data); $i++) {
                $p[$xls_data[$i]['A']][run] = $xls_data[$i]['C'];
                $p[$xls_data[$i]['A']][y][] = $xls_data[$i]['E'];
                $p[$xls_data[$i]['A']][x][] = $xls_data[$i]['B'];
                $p[$xls_data[$i]['A']][slope][] = $xls_data[$i]['F'];
                $p[$xls_data[$i]['A']][speed][] = $xls_data[$i]['D'];
            }

            for($i = 1; $i <= count($p); $i++) {

                sort($p[$i][y]);

                $date1 = $p[$i][x][0];

                for($j = 0; $j < count($p[$i][x]); $j++){

                    $date2 = $p[$i][x][$j];
                    $speed = $p[$i][speed][$j];
                    //$d = strtotime( str_replace("/", "-", $p[$i][x][$j+1] ) ). '|' .strtotime( str_replace("/", "-", $p[$i][x][$j] ) );
                    //($p[$i][speed][$j] - $p[$i][speed][$j+1])
                    //echo $d;

                    $p[$i][charts][m][] = array('x' => strtotime( str_replace("/", "-", $date2 ) ) - strtotime( str_replace("/", "-", $date1 ) ), 'y' => $p[$i][y][$j]);
                    $p[$i][charts][s][] = array('x' => strtotime( str_replace("/", "-", $date2 ) ) - strtotime( str_replace("/", "-", $date1 ) ), 'y' => $speed);
                    //$p[$i][charts][d][] = array('x' => strtotime( str_replace("/", "-", $date2 ) ) - strtotime( str_replace("/", "-", $date1 ) ), 'y' => $d);

                }

            }

            for($i = 1; $i <= count($p); $i++) {

                $date1 = $p[$i][x][0];

                for($j = 0; $j < count($p[$i][x])-1; $j++){

                    $date2 = $p[$i][x][$j];
                    $d = ( $p[$i][speed][$j] - $p[$i][speed][$j+1] ) / ( strtotime( str_replace("/", "-", $p[$i][x][$j+1] ) ) - strtotime( str_replace("/", "-", $p[$i][x][$j] ) ) );

                    $p[$i][charts][d][] = array('x' => strtotime( str_replace("/", "-", $date2 ) ) - strtotime( str_replace("/", "-", $date1 ) ), 'y' => $d);

                }
                echo '<pre>';
                print_r($p[$i][d]);
                echo '</pre>';

            }

            for($i = 1; $i <= count($p); $i++) {
                $p[$i][data] = array(
                    's' => strtotime( str_replace("/", "-", max( $p[$i][x] ) ) ) - strtotime( str_replace("/", "-", min( $p[$i][x] ) ) ),
                    'm' => max( $p[$i][y] ) - min( $p[$i][y] ),
                    'd' => ( $p[$i][speed][0] - $p[$i][speed][count($p[$i][speed])] ) / ( strtotime( str_replace("/", "-", max( $p[$i][x] ) ) ) - strtotime( str_replace("/", "-", min( $p[$i][x] ) ) ) ),
                );
            }

//                echo count($p);
//                echo '<pre>';
//                print_r($p[1][charts]);
//                echo '</pre>';

            echo "<div style='clear: both; '></div>";
            echo "<div style='width: 30%; height: 600px;float:left;' class='ct-chart-m'></div>";
            echo "<div style='width: 30%; height: 600px; float:left; ' class='ct-chart-s'></div>";
            echo "<div style='width: 30%; height: 600px; float:left; ' class='ct-chart-d'></div>";
            echo "<div style='clear: both; '></div>";


            echo "<table style='text-align: center; width: 100%;'>";
            echo "<tr><td>№ run</td><td>CheckBox</td><td>№ train</td><td>Total duration (m/s)</td><td>Path length (m)</td><td>Slowdown D (m/s^2)</td><td>Good/Bad slowdown +/-</td></tr>";

            $i = 0;

            foreach ($p as $t){

                $i++;

                echo "<tr>";
                echo "<td>$i</td>";
                echo "<td><input type='checkbox' id='chart_$i' class='chart' name='chart[]' value='" . json_encode($p[$i][charts]) . "'></td>";
                echo "<td>{$t[run]}</td>";
                echo "<td>{$t[data][s]}</td>";
                echo "<td>{$t[data][m]}</td>";
                echo "<td>{$t[data][d]}</td>";
                echo "<td>" . checkT($t[data][d], 0.93) . "</td>";
                echo "</tr>";

            }

            echo "</table>";

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', '№ run');
            $sheet->setCellValue('B1', '№ train');
            $sheet->setCellValue('C1', 'Total duration (m/s)');
            $sheet->setCellValue('D1', 'Path length  (m)');
            $sheet->setCellValue('E1', 'Slowdown D (m/s^2)');
            $sheet->setCellValue('F1', 'Good/Bad slowdown +/-');

            $i = 1;

            foreach ($p as $t){

                $i++;

                $sheet->setCellValue('A'.$i, $i-1);
                $sheet->setCellValue('B'.$i, $t[run]);
                $sheet->setCellValue('C'.$i, $t[data][s]);
                $sheet->setCellValue('D'.$i, $t[data][m]);
                $sheet->setCellValue('E'.$i, $t[data][d]);
                $sheet->setCellValue('F'.$i, checkT($t[data][d], 0.93));

            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('o/' . $outputfile . '.xlsx');

        } else {
            echo "<span style='color: red;'>Failed upload : {$_FILES['file']['name']}</span>";
        }

    } else {
        echo "<span style='color: red;'>Failed upload : {$_FILES['file']['name']}</span>";
    }

}


?>

<script>



    // function chartF(button, value) {
    //
    //
    //
    //
    //
    // }

    $(document).ready(function() {

        let ids = [];
        let Mchart = [];
        let Schart = [];
        let Dchart = [];

        $('input.chart').change(function(){

            //console.log(this.value);
            let button = this;
            let value = JSON.parse(this.value);

            if (button.checked == true) {

                ids.push(button.id);
                Mchart.push(value.m);
                Schart.push(value.s);
                Dchart.push(value.d);
                // console.log(Mchart);
                // console.log(ids);

            } else {

                Mchart.splice(ids.indexOf(button.id));
                Schart.splice(ids.indexOf(button.id));
                Dchart.splice(ids.indexOf(button.id));
                ids.splice(ids.indexOf(button.id));

                // console.log(Mchart);
                // console.log(ids);
            }

            chartM(Mchart);
            chartS(Schart);
            chartD(Dchart);

        });

        function chartM(value){

            var data1 = { series:value };

            var options1 = {
                fullWidth: true,
                axisX: {
                    type: Chartist.AutoScaleAxis
                },
                axisY: {
                    type: Chartist.AutoScaleAxis,
                    offset: 80,
                    labelInterpolationFnc: function(value) {
                        return value + ' m'
                    },
                },
                plugins: [
                    Chartist.plugins.zoom({ onZoom: onZoom1 })
                ]
            };

            var chart1 = Chartist.Line('.ct-chart-m', data1, options1);
            var resetFnc1;
            function onZoom1(chart1, reset1) {
                resetFnc1 = reset1;
            }

            var btn = document.createElement('button');
            btn.id = 'reset-zoom-btn';
            btn.innerHTML = 'Reset Zoom';
            btn.style.float = 'right';
            btn.addEventListener('click', function() {
                console.log(resetFnc1);
                resetFnc1 && resetFnc();
            });
            var parent = document.querySelector('#example-plugin-zoom .chart');
            // !parent.querySelector('#reset-zoom-btn') && parent.appendChild(btn);

        }

        function chartS(value){

            var data = { series:value };

            var options = {
                fullWidth: true,
                axisX: {
                    type: Chartist.AutoScaleAxis
                },
                axisY: {
                    type: Chartist.AutoScaleAxis,
                    offset: 80,
                    labelInterpolationFnc: function(value) {
                        return value + ' m/s'
                    },
                },
                plugins: [
                    Chartist.plugins.zoom({ onZoom: onZoom2 })
                ]

            };

            var chart = Chartist.Line('.ct-chart-s', data, options);
            var resetFnc;
            function onZoom2(chart, reset) {
                resetFnc = reset;
            }

            var btn = document.createElement('button');
            btn.id = 'reset-zoom-btn';
            btn.innerHTML = 'Reset Zoom';
            btn.style.float = 'right';
            btn.addEventListener('click', function() {
                console.log(resetFnc);
                resetFnc && resetFnc();
            });
            var parent = document.querySelector('#example-plugin-zoom .chart');
            //!parent.querySelector('#reset-zoom-btn') && parent.appendChild(btn);

        }

        function chartD(value){

            var data = { series:value };

            var options = {
                fullWidth: true,
                axisX: {
                    type: Chartist.AutoScaleAxis
                },
                axisY: {
                    type: Chartist.AutoScaleAxis,
                    offset: 80,
                    labelInterpolationFnc: function(value) {
                        return value + ' m/s^2'
                    },
                },
                plugins: [
                    Chartist.plugins.zoom({ onZoom: onZoom3 })
                ]

            };

            var chart = Chartist.Line('.ct-chart-d', data, options);
            var resetFnc;
            function onZoom3(chart, reset) {
                resetFnc = reset;
            }

            var btn = document.createElement('button');
            btn.id = 'reset-zoom-btn';
            btn.innerHTML = 'Reset Zoom';
            btn.style.float = 'right';
            btn.addEventListener('click', function() {
                console.log(resetFnc);
                resetFnc && resetFnc();
            });
            var parent = document.querySelector('#example-plugin-zoom .chart-d');
            //!parent.querySelector('#reset-zoom-btn') && parent.appendChild(btn);

        }

        // var data2 = { series:[{}] };
        //
        // var options2 = {
        //     fullWidth: false,
        //     axisX: {
        //         type: Chartist.AutoScaleAxis
        //     },
        //     axisY: {
        //         type: Chartist.AutoScaleAxis,
        //         offset: 80,
        //         labelInterpolationFnc: function(value) {
        //             return value + ' s'
        //         },
        //     },
        //
        // };
        //
        // var chart2 = Chartist.Line('.ct-chart-s', data2, options2);
        // var resetFnc2;
        // function onZoom(chart2, reset2) {
        //     resetFnc2 = reset2;
        // }
        //
        // var btn = document.createElement('button');
        // btn.id = 'reset-zoom-btn';
        // btn.innerHTML = 'Reset Zoom';
        // btn.style.float = 'right';
        // btn.addEventListener('click', function() {
        //     console.log(resetFnc2);
        //     resetFnc2 && resetFnc();
        // });
        // var parent = document.querySelector('#example-plugin-zoom .chart');
        // !parent.querySelector('#reset-zoom-btn') && parent.appendChild(btn);

    });

    // (function() {
    //
    //     //alert(1);
    //
    //
    //
    //     // $("input.chart").on('change', function() {
    //     //
    //     //     var chart = [];
    //     //
    //     //     console.log(Math.max($('input.chart:checked').length));
    //     //
    //     //     for(var i = 1; i <= $('input.chart').length; i++){
    //     //         console.log(i);
    //     //         var jsonArray = JSON.parse($('input#chart_'+i+':checked').val());
    //     //
    //     //         chart.push( jsonArray.chart );
    //     //
    //     //     }
    //     //
    //     //     console.log(chart);
    //     //
    //     //
    //     //     var data = {
    //     //         series:chart
    //     //     };
    //     //
    //     //     var options = {
    //     //         fullWidth: true,
    //     //         axisX: {
    //     //             type: Chartist.AutoScaleAxis
    //     //         },
    //     //         axisY: {
    //     //             type: Chartist.AutoScaleAxis,
    //     //             offset: 80,
    //     //             labelInterpolationFnc: function(value) {
    //     //                 return value + ' m'
    //     //             },
    //     //         },
    //     //         plugins: [
    //     //             Chartist.plugins.zoom({ onZoom: onZoom })
    //     //         ]
    //     //     };
    //     //
    //     //     var chart = Chartist.Line('.ct-chart', data, options);
    //     //     var resetFnc;
    //     //     function onZoom(chart, reset) {
    //     //         resetFnc = reset;
    //     //     }
    //     //
    //     //     var btn = document.createElement('button');
    //     //     btn.id = 'reset-zoom-btn';
    //     //     btn.innerHTML = 'Reset Zoom';
    //     //     btn.style.float = 'right';
    //     //     btn.addEventListener('click', function() {
    //     //         console.log(resetFnc);
    //     //         resetFnc && resetFnc();
    //     //     });
    //     //     var parent = document.querySelector('#example-plugin-zoom .chart');
    //     //     !parent.querySelector('#reset-zoom-btn') && parent.appendChild(btn);
    //     //
    //     // });
    //
    // });

</script>






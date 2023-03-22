<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <style>
        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
        }
        section {
            position:absolute;
            width:100%;
            height:100%;
        }

        .top-left {
            position:absolute;
            top:0;
            left:0;
            right:50%;
            bottom:50%;
            /* background-color:red; */
        }

        .top-right {
            position:absolute;
            top:0;
            left:50%;
            right:0;
            bottom:50%;
            /* background-color:blue;     */
        }

        .bottom-left {
            position:absolute;
            top:50%;
            left:0;
            right:50%;
            bottom:0;
            /* background-color:green; */
        }

        .bottom-right {
            position:absolute;
            top:50%;
            left:50%;
            right:0;
            bottom:0;
            /* background-color:gold; */
        }

        table,td,th {
            border: 1px solid gray;
        }
        table {
            border-collapse : collapse;
            width : 80%;
            height: 80%;
            margin: auto;
            text-align: center;
        }
        th {
            background-color: #8b9ba7;
            font-weight: bold;
            color: #ffffff;
            font-size : large;
        }
 
        tr>td:first-child {
            background-color: #e4e7ea;
            font-weight: bold;
            font-size : large;
        }
        caption {
            color: gray;
            font-size : x-large;
        }
        .title {
            font-weight: bold;
        }
        .sub-title {
            margin: 0;
            text-align:right;
        }
    </style>
    <title>CMS 통계</title>
</head>
    <body>
        <section>
            <div class="top-left">
                <table>
                    <caption>
                        <span class="title">아카이브 건수</span>
                        <br>
                        <h6 class="sub-title">날짜 : (전체)</h6>
                    </caption>
                    <colgroup>
                        <col width="30%">
                        <col width="35%">
                        <col width="35%">
                    </colgroup>
                    <th>항목</th>
                    <th>건수</th>
                    <th>용량</th>
                    <?php
                        $totalCnt = 0;
                        $totalGb = 0.00;
                        foreach($this->getData('archive') as $archive){
                          echo "<tr>";
                          echo $this->tag('td', $archive->ud_content_title);
                          echo $this->tag('td', $archive->cnt);
                          echo $this->tag('td', (float)$archive->filesize_gb."GB");
                          echo "</tr>";

                          $totalCnt += $archive->cnt;
                          $totalGb += $archive->filesize_gb;
                        };
                    ?>
                    <tr>
                        <td class="row-title">Total</td>
                        <td><?=$totalCnt?></td>
                        <td><?=$totalGb.'GB'?></td>
                    </tr>
                </table>
            </div>
            <div class="top-right">
                <table>
                    <caption>
                        <span class="title">스토리지 용량</span>
                        <br>
                        <h6 class="sub-title">날짜:<?=date('m월 d일')?>(1일)</h6>
                    </caption>
                    <colgroup>
                        <col width="20%">
                        <col width="20%">
                        <col width="20%">
                        <col width="20%">
                        <col width="20%">
                    </colgroup>
                    <th>항목</th>
                    <th>전체</th>
                    <th>사용량</th>
                    <th>가 용량</th>
                    <th>사용량</th>                    
                    <?php
                        foreach ($this->getData('storage') as $key => $row) {
                            echo "<tr>";
                            if($row->drive == 'X:'){                              
                                echo $this->tag('td', '메인');
                            }else if($row->drive == 'O:'){      
                                echo $this->tag('td', '니어라인');
                            }else if($row->drive == 'L:'){      
                                echo $this->tag('td', '저해상도');
                            }
                            echo $this->tag('td', $row->total_size . ' TB');
                            echo $this->tag('td', $row->used_size. ' TB');
                            echo $this->tag('td', $row->remaining_size. ' TB');
                            echo $this->tag('td', $row->used_percent. ' %');                               
                            echo "</tr>";
                        }

                        foreach ($this->getData('divaTape') as $key => $row) {
                            echo "<tr>";
                            if($row->ta_type == 'main'){                              
                                echo $this->tag('td', 'DTL 원본');
                                echo $this->tag('td', $row->total_size . ' TB');
                                echo $this->tag('td', $row->used_size. ' TB');
                                echo $this->tag('td', $row->remaining_size. ' TB');
                                echo $this->tag('td', $row->used_percent. ' %'); 
                            }else if($row->ta_type == 'backup'){      
                                echo $this->tag('td', 'DTL 복본');
                                echo $this->tag('td', $row->total_size . ' TB');
                                echo $this->tag('td', $row->used_size. ' TB');
                                echo $this->tag('td', $row->remaining_size. ' TB');
                                echo $this->tag('td', $row->used_percent. ' %'); 
                            }                            
                            echo "</tr>";
                        }
                        ?>
                </table>
            </div>
            <div class="bottom-left">
                <table>
                    <caption>
                        <span class="title">콘텐츠 등록 건수</span>
                        <br>
                        <h6 class="sub-title">날짜:<?=date('m월 d일',strtotime("-2 week"))?> ~ <?=date('m월 d일')?>(2주)</h6>
                    </caption>
                    <colgroup>
                        <col width="20%">
                        <col width="20%">
                        <col width="20%">
                        <col width="20%">
                        <col width="20%">
                    </colgroup>
                    <th>항목</th>
                    <th>요청 건</th>
                    <th>반려 건</th>
                    <th>승인</th>
                    <th>삭제</th>
                    <?php
                        foreach($this->getData('registration') as $type =>$registration){
                            echo "<tr>";
                                echo $this->tag('td', $registration['title']);
                                echo $this->tag('td', $registration['request']);
                                echo $this->tag('td', $registration['reject']);
                                echo $this->tag('td', $registration['approval']);
                                echo $this->tag('td', $registration['delete']);
                            echo "</tr>";
                        }
                    ?>
                </table>
            </div>
            <div class="bottom-right">
            <table>
                    <caption>
                        <span class="title">포털 업로드/다운로드</span>
                        <br>
                        <h6 class="sub-title">날짜:(전체)</h6>
                    </caption>
                    <colgroup>
                        <col width="20%">
                        <col width="20%">
                        <col width="20%">
                        <col width="20%">
                        <col width="20%">
                    </colgroup>
                    <th rowspan=2>항목</th>
                    <th colspan=2>내부</th>
                    <th colspan=2>외부</th>
                    <tr>
                        <th>다운로드</th>
                        <th>업로드</th>
                        <th>다운로드</th>
                        <th>업로드</th>
                    </tr>
                    <?php
                        $total = [
                            'inner_download' => 0,
                            'inner_upload' => 0,
                            'external_download' => 0,
                            'external_upload' => 0,
                        ];
                        foreach ($this->getData('portal') as $type =>$portal) {
                            echo "<tr>";
                                echo $this->tag('td', $type);
                                echo $this->tag('td', $portal['inner']['download']);
                                echo $this->tag('td', $portal['inner']['upload']);
                                echo $this->tag('td', $portal['external']['download']);
                                echo $this->tag('td', $portal['external']['upload']);
                            echo "</tr>";

                            $total['inner_download'] += $portal['inner']['download'];
                            $total['inner_upload'] += $portal['inner']['upload'];
                            $total['external_download'] += $portal['external']['download'];
                            $total['external_upload'] += $portal['external']['upload'];
                        }
                    ?>
                    <tr>
                        <td>Total</td>
                        <td><?=$total['inner_download']?></td>
                        <td><?=$total['inner_upload']?></td>
                        <td><?=$total['external_download']?></td>
                        <td><?=$total['external_upload']?></td>
                    </tr>
                </table>
            </div>
        </section>
    </body>
</html
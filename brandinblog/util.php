<?php
date_default_timezone_set('Asia/Shanghai');

class Util {

  public static $LANG = 'en'; // 'en', 'zh' or 'ja'

  public static function langtext($en, $zh, $ja = Null) {
    if (self::$LANG === 'zh')
      return $zh;
    elseif (self::$LANG === 'ja')
      return ($ja === Null ? $zh : $ja);
    return $en;
  }

  public static $EMOTIONCOLORS = array('#FFFF54', '#FF0000', '#5151FF', '#009600', '#59BDFF');
  // happiness, anger, sadness, fear, surprise

  public static $EMOTIONCOLORGRAY = '#717063';

  public static $CHINESENUMBERS = array('○', '一', '二', '三', '四', '五', '六', '七', '八', '九', '十', '十一', '十二');

  public static function get_month_name($month) {
    return self::langtext(date("M", mktime(0, 0, 0, $month, 1)), self::$CHINESENUMBERS[$month] . '月');
  }

  public static function get_day_name($day) {
    return self::langtext(date("jS", mktime(0, 0, 0, 0, $day)), $day . '日');
  }

  public static $PROVINCES = array(
    11 => array('Beijing', '北京', '北京'),
    50 => array('Chongqing', '重庆', '重慶'),
    31 => array('Shanghai', '上海', '上海'),
    12 => array('Tianjin', '天津', '天津'),
    34 => array('Anhui', '安徽', '安徽'),
    35 => array('Fujian', '福建', '福建'),
    62 => array('Gansu', '甘肃', '甘粛'),
    44 => array('Guangdong', '广东', '広東'),
    52 => array('Guizhou', '贵州', '貴州'),
    46 => array('Hainan', '海南', '海南'),
    13 => array('Hebei', '河北', '河北'),
    23 => array('Heilongjiang', '黑龙江', '黒竜江'),
    41 => array('Henan', '河南', '河南'),
    42 => array('Hubei', '湖北', '湖北'),
    43 => array('Hunan', '湖南', '湖南'),
    32 => array('Jiangsu', '江苏', '江蘇'),
    36 => array('Jiangxi', '江西', '江西'),
    22 => array('Jilin', '吉林', '吉林'),
    21 => array('Liaoning', '辽宁', '遼寧'),
    63 => array('Qinghai', '青海', '青海'),
    61 => array('Shaanxi', '陕西', '陝西'),
    37 => array('Shandong', '山东', '山東'),
    14 => array('Shanxi', '山西', '山西'),
    51 => array('Sichuan', '四川', '四川'),
    71 => array('Taiwan', '台湾', '台湾'), // TW
    53 => array('Yunnan', '云南', '雲南'),
    33 => array('Zhejiang', '浙江', '浙江'),
    45 => array('Guangxi', '广西', '広西'),
    15 => array('Nei Mongol', '内蒙古', '内モンゴル'),
    64 => array('Ningxia', '宁夏', '寧夏'),
    65 => array('Xinjiang', '新疆', '新疆'),
    54 => array('Xizang', '西藏', 'チベット'),
    81 => array('Hong Kong', '香港', '香港'), // HK, ISO: 91
    82 => array('Macao', '澳门', 'マカオ') // MO, ISO: 92
  );

  public static $BEIJINGDISTRICTS = array(
    1 => array('Dongcheng', '东城区', '東城区'),
    2 => array('Xicheng', '西城区', '西城区'),
    3 => array('Chongwen', '崇文区', '崇文区'),
    4 => array('Xuanwu', '宣武区', '宣武区'),
    5 => array('Chaoyang', '朝阳区', '朝陽区'),
    6 => array('Fengtai', '丰台区', '豊台区'),
    7 => array('Shijingshan', '石景山区', '石景山区'),
    8 => array('Haidian', '海淀区', '海淀区'),
    9 => array('Mentougou', '门头沟区', '門頭溝区'),
    11 => array('Fangshan', '房山区', '房山区'),
    12 => array('Tongzhou', '通州区', '通州区'),
    13 => array('Shunyi', '顺义区', '順義区'),
    14 => array('Changping', '昌平区', '昌平区'),
    15 => array('Daxing', '大兴区', '大興区'),
    16 => array('Huairou', '怀柔区', '懐柔区'),
    17 => array('Pinggu', '平谷区', '平谷区'),
    28 => array('Miyun', '密云县', '密雲県'),
    29 => array('Yanqing', '延庆县', '延慶県')
  );

  public static $PRODUCTS = array(
    array('Samsung', '三星', 'Samsung'),
    array('Xiaomi', '小米', 'Xiaomi'),
    array('Huawei', '华为', 'Huawei'),
    array('Apple', '苹果', 'Apple')
  );
}
?>

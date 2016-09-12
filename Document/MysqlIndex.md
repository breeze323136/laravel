# Mysql索引学习笔记(一)

![](https://img.shields.io/github/stars/pandao/editor.md.svg) ![](https://img.shields.io/github/forks/pandao/editor.md.svg) ![](https://img.shields.io/github/tag/pandao/editor.md.svg) ![](https://img.shields.io/github/release/pandao/editor.md.svg) ![](https://img.shields.io/github/issues/pandao/editor.md.svg) ![](https://img.shields.io/bower/v/editor.md.svg)

**目录 (Table of Contents)**

[TOC]

-------------
                
###前言
关于MySQL索引的好处，如果正确合理设计并且使用索引的MySQL是一辆兰博基尼的话，那么没有设计和使用索引的MySQL就是一个人力三轮车。对于没有索引的表，单表查询可能几十万数据就是瓶颈，而通常大型网站单日就可能会产生几十万甚至几百万的数据，没有索引查询会变的非常缓慢。
`日常实例`
```
原来跳槽的时候去了一家公司，用户表在3万左右，每次查询商品时会设计多表关连的操作，商品表不过就两千多条数据，可每次执行的时间在4s以上，查看数据库发现用户表居然没有索引，于是我就在用户表中加入了搜索后，执行的时间居然是0.45s，不得不说使用人力三轮车与开兰博基尼的区别还真大。
```

MySQL凭借着`出色的性能`、`低廉的成本`、`丰富的资源`，已经成为绝大多数互联网公司的首选关系型数据库。所谓出色的性能，应该为我们每一位开发工程师都需要去学习和参考的，要不写出来一个加载速度连自己都受不了的应用，就非是喜欢代码的码农啦。

在工程师求职的道路上相信都能意识到别人会问你很多有关于Mysql的问题，而问Mysql的问题之中，问的次数和深入去问的频率中`索引优化`是你必不可少的一道门槛，以前可能面对突如其来的面试总是冲忙地恶补一下，等有点空闲时间了，我们应该更仔细，全面，深入地去学习和了解Mysql索引。

`一般的应用系统，读写的比例是10:1左右`，所以才数据库前期设计中需要先了解哪些是读表多，哪些是写表多的需求，在插入和更新的操作中一般都很少出现问题，在单机服务中可以利用事务回滚来实现写入操作的防范，在分布式的应用服务中，则需要利用详细的逻辑判断进行监控和队列处理写入操作，能保证数据的一致性和完整性，一般写的性能是没啥太大问题的。遇到最多的，也是最容易出问题的，还是一些复杂的查询操作，所以查询优化显然是重中之重。

###一个慢查询引发的思考
```
select
   count(*) 
from
   task 
where
   status=2 
   and operator_id=20839 
   and operate_time>1371169729 
   and operate_time<1371174603 
   and type=2;
```

系统使用者反应有一个功能越来越慢，于是工程师找到了上面的SQL。
并且兴致冲冲的找到了我，“这个SQL需要优化，给我把每个字段都加上索引”
我很惊讶，问道“`为什么需要每个字段都加上索引？`”
“把查询的字段都加上索引会更快”工程师信心满满
“这种情况完全可以建一个`联合索引`，因为是`最左前缀匹配`，所以operate_time需要放到最后，而且还需要把其他相关的查询都拿来，需要做一个综合评估。”
“`联合索引？最左前缀匹配？综合评估？`”工程师不禁陷入了沉思。
多数情况下，我们知道索引能够提高查询效率，但应该如何建立索引？索引的顺序如何？许多人却只知道大概。其实理解这些概念并不难，而且索引的原理远没有想象的那么复杂。

其实排除在学生时代，在实际工作中，我们遇到的问题千奇百怪，五花八门，匪夷所思，而我们似乎都知道联合索引，但未必都了解什么叫联合索引，或者是会说知道原理却道不出所以然来。

`举一反三`
demo: 假设现在有brand品牌表，category分类表，goods商品表，一般在电商多级分类中，都不会直接通过品牌去找商品，而是通过分类进来后，再选择品牌，查找该品牌下的所有商品。如果是让你给goods表添加branch_id和category_id的索引我们会怎么加？

其实这个问题我是在一次面试机会中，那一位特别好的一个技术领导给我出的问题，当然后面还是他很热心地给我详细讲解了索引以及联合索引，怎么查看索引的比对等。

我的回答：
```
/*怎么在商品表中加brand_id和category_id的索引？？？
我使用了Sequel pro工具给一个个加上的索引(单列索引)
Mysql命令为:*/
ALTER TABLE `goods` ADD INDEX fk_brand_id (`brand_id`)
ALTER TABLE `goods` ADD INDEX fk_category_id (`category_id`)
```

当然我的回答不能说错，但就是不合理,所以面试的技术领导跟我讲了联合索引，并给我展示了`单列索引`和`联合索引`在这个问题中的性能对比。
```sql
ALTER TABLE `goods` ADD INDEX fk_category_id_fk_brand_id (`brand_id`,`category_id`)

/*表结构的索引则从：*/

  KEY `category_id_2` (`category_id`),
  KEY `brand_id` (`brand_id`),
  
/*变为了：*/

 KEY `fk_category_id_fk_brand_id` (`category_id`,`brand_id`)
```

说了那么多，那么联合索引在这样的实际中，比单列索引有什么好处呢？
我们先通过`Explain命令来显示mysql如何使用索引来处理select语句以及连接表`。可以帮助选择更好的索引和写出更优化的查询语句

---
表的主要结构
```
/* InnoDB类型，则读的性能没有MyISAM的好，有3954条记录,只有一个主键索引*/
CREATE TABLE `app_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `brand_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '品牌id，取值于ecs_brand 的brand_id',
  `category_id` int(11) DEFAULT NULL COMMENT '分类id',
  `pid` int(11) DEFAULT NULL COMMENT '发布者id',
   PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3954 DEFAULT CHARSET=utf8;
```

未添加brand_id和category_id的处理:
```
/*命令*/
explain select * from `app_goods` where `brand_id`=10;

/*索引处理过程,从3262条记录中查找*/
id, select_type, table, type, possible_keys, key, key_len,ref, rows, Extra

1, SIMPLE, app_goods, ALL, NULL, NULL, NULL, NULL, 3262, Using where);
```

查看主键搜索的处理:
```sql
/*命令*/
explain select * from `app_goods` where `id`=2091;

/*索引处理过程 列出是通过常量（const）有4个主键*/
id, select_type, table, type, possible_keys, key, key_len,ref, rows, Extra

1	SIMPLE	app_goods	const	PRIMARY	PRIMARY	4	const	1	NULL
```

查看只有brand_id索引的处理:
```sql
/*命令*/
alter table `app_goods` add index brand_id(`brand_id`)
explain select * from `app_goods` where `brand_id` in  (select id from app_goods where category_id=3);

/*索引处理过程*/
id, select_type, table, type, possible_keys, key, key_len,ref, rows, Extra

1	SIMPLE	app_goods	ALL	brand_id	NULL	NULL	NULL	3262	NULL
1	SIMPLE	app_goods	eq_ref	PRIMARY	PRIMARY	4	develop.app_goods.brand_id	1	Using where


explain select * from `app_goods` where `category_id`=3 and `brand_id`=12;

/*索引处理过程*/
id, select_type, table, type, possible_keys, key, key_len,ref, rows, Extra

1	SIMPLE	app_goods	ref	brand_id	brand_id	2	const	1	Using where
```
---



`查看添加单列索引brand_id和category_id索引的处理:`
```sql
/*命令*/
ALTER TABLE `app_goods` ADD INDEX fk_category_id_fk_brand_id (`category_id`,`brand_id`)

explain select * from `app_goods` where `category_id` in  (select id from app_goods where brand_id=12);

/*索引处理过程 */
id, select_type, table, type, possible_keys, key, key_len,ref, rows, Extra

1	SIMPLE	app_goods	ALL	fk_category_id_fk_brand_id	NULL	NULL	NULL	3262	Using where
1	SIMPLE	app_goods	eq_ref	PRIMARY	PRIMARY	4	develop.app_goods.category_id	1	Using where

/* 可以通过子查看和explain看出，如果根据brand_id查询会扫全表记录，而通过category_id先查询的是会根据主键去查，ADD INDEX fk_category_id_fk_brand_id (`category_id`,`brand_id`)不能先写brand_id在前，原因是联合索引的话, 它往往计算的是第一个字段(最左边那个)*/
```
---

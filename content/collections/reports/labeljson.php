<?php
 $LABEL_FORMAT_JSON = '{
    "labelFormats": [
        {
            "title": "Generic Herbarium Label",
            "displaySpeciesAuthor": 1,
            "displayBarcode": 0,
            "labelType": "2",
            "customStyles": "body{ font-size:10pt; }",
            "defaultCss": "..\/..\/css\/v202209\/symbiota\/collections\/reports\/labelhelpers.css",
            "customCss": "",
            "customJs": "",
            "pageSize": "letter",
            "labelHeader": {
                "prefix": "Flora of ",
                "midText": 3,
                "suffix": " county",
                "className": "text-center font-bold font-sans text-2xl",
                "style": "margin-bottom:10px;"
            },
            "labelBlocks": [
                {
                    "divBlock": {
                        "className": "label-block",
                        "blocks": [
                            {
                                "divBlock": {
                                    "className": "taxonomy my-2 text-lg",
                                    "blocks": [
                                        {
                                            "fieldBlock": [
                                                {
                                                    "field": "identificationqualifier"
                                                },
                                                {
                                                    "field": "speciesname",
                                                    "className": "font-bold italic"
                                                },
                                                {
                                                    "field": "parentauthor"
                                                },
                                                {
                                                    "field": "taxonrank",
                                                    "className": "font-bold"
                                                },
                                                {
                                                    "field": "infraspecificepithet",
                                                    "className": "font-bold italic"
                                                },
                                                {
                                                    "field": "scientificnameauthorship"
                                                }
                                            ],
                                            "delimiter": " "
                                        },
                                        {
                                            "fieldBlock": [
                                                {
                                                    "field": "family",
                                                    "styles": [
                                                        "float:right"
                                                    ]
                                                }
                                            ]
                                        }
                                    ]
                                }
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "identifiedby",
                                        "prefix": "Det by: "
                                    },
                                    {
                                        "field": "dateidentified"
                                    }
                                ]
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "identificationreferences"
                                    }
                                ]
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "identificationremarks"
                                    }
                                ]
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "taxonremarks"
                                    }
                                ]
                            },
                            {
                                "divBlock": {
                                    "className": "text-lg",
                                    "style": "margin-top:10px;",
                                    "blocks": [
                                        {
                                            "fieldBlock": [
                                                {
                                                    "field": "country",
                                                    "className": "font-bold"
                                                },
                                                {
                                                    "field": "stateprovince",
                                                    "style": "font-weight:bold"
                                                },
                                                {
                                                    "field": "county"
                                                },
                                                {
                                                    "field": "municipality"
                                                },
                                                {
                                                    "field": "locality"
                                                }
                                            ],
                                            "delimiter": ", "
                                        }
                                    ]
                                }
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "decimallatitude"
                                    },
                                    {
                                        "field": "decimallongitude",
                                        "style": "margin-left:10px"
                                    },
                                    {
                                        "field": "coordinateuncertaintyinmeters",
                                        "prefix": "+-",
                                        "suffix": " meters",
                                        "style": "margin-left:10px"
                                    },
                                    {
                                        "field": "geodeticdatum",
                                        "prefix": "[",
                                        "suffix": "]",
                                        "style": "margin-left:10px"
                                    }
                                ]
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "verbatimcoordinates"
                                    }
                                ]
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "elevationinmeters",
                                        "prefix": "Elev: ",
                                        "suffix": "m. "
                                    },
                                    {
                                        "field": "verbatimelevation"
                                    }
                                ]
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "habitat",
                                        "suffix": "."
                                    }
                                ]
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "substrate",
                                        "suffix": "."
                                    }
                                ]
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "verbatimattributes"
                                    },
                                    {
                                        "field": "establishmentmeans"
                                    }
                                ],
                                "delimiter": "; "
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "associatedtaxa",
                                        "prefix": "Associated species: ",
                                        "className": "italic"
                                    }
                                ]
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "occurrenceremarks"
                                    }
                                ]
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "typestatus"
                                    }
                                ]
                            },
                            {
                                "divBlock": {
                                    "className": "collector",
                                    "style": "margin-top:10px;",
                                    "blocks": [
                                        {
                                            "fieldBlock": [
                                                {
                                                    "field": "recordedby",
                                                    "style": "float:left"
                                                },
                                                {
                                                    "field": "recordnumber",
                                                    "style": "float:left;margin-left:10px"
                                                },
                                                {
                                                    "field": "eventdate",
                                                    "style": "float:right"
                                                }
                                            ]
                                        },
                                        {
                                            "fieldBlock": [
                                                {
                                                    "field": "associatedcollectors",
                                                    "prefix": "with: "
                                                }
                                            ],
                                            "style": "clear:both; margin-left:10px;"
                                        }
                                    ]
                                }
                            }
                        ]
                    }
                }
            ],
            "labelFooter": {
                "textValue": "",
                "className": "text-center font-bold font-sans",
                "style": "margin-top:10px;"
            }
        },
        {
            "title": "Generic Vertebrate Label",
            "displaySpeciesAuthor": 0,
            "displayBarcode": 0,
            "labelType": "3",
            "customStyles": "body{ font-size:10pt; }",
            "defaultCss": "..\/..\/css\/v202209\/symbiota\/collections\/reports\/labelhelpers.css",
            "customCss": "",
            "customJs": "",
            "pageSize": "letter",
            "labelHeader": {
                "prefix": "",
                "midText": 0,
                "suffix": "",
                "className": "text-center font-bold font-sans text-2xl",
                "style": "text-align:center;margin-bottom:5px;font:bold 7pt arial,sans-serif;clear:both;"
            },
            "labelFooter": {
                "textValue": "",
                "className": "text-center font-bold font-sans text-2xl",
                "style": "text-align:center;margin-top:10px;font:bold 10pt arial,sans-serif;clear:both;"
            },
            "labelBlocks": [
                {
                    "divBlock": {
                        "className": "labelBlockDiv",
                        "blocks": [
                            {
                                "fieldBlock": [
                                    {
                                        "field": "family",
                                        "styles": [
                                            "margin-bottom:2px;font-size:pt"
                                        ]
                                    }
                                ]
                            },
                            {
                                "divBlock": {
                                    "className": "taxonomyDiv",
                                    "style": "font-size:10pt;",
                                    "blocks": [
                                        {
                                            "fieldBlock": [
                                                {
                                                    "field": "identificationqualifier"
                                                },
                                                {
                                                    "field": "speciesname",
                                                    "style": "font-weight:bold;font-style:italic"
                                                },
                                                {
                                                    "field": "parentauthor"
                                                },
                                                {
                                                    "field": "taxonrank",
                                                    "style": "font-weight:bold"
                                                },
                                                {
                                                    "field": "infraspecificepithet",
                                                    "style": "font-weight:bold;font-style:italic"
                                                },
                                                {
                                                    "field": "scientificnameauthorship"
                                                }
                                            ],
                                            "delimiter": " "
                                        }
                                    ]
                                }
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "identifiedby",
                                        "prefix": "Det by: "
                                    },
                                    {
                                        "field": "dateidentified"
                                    }
                                ]
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "identificationreferences"
                                    }
                                ]
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "identificationremarks"
                                    }
                                ]
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "taxonremarks"
                                    }
                                ]
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "catalognumber",
                                        "style": "font-weight:bold;font-size:14pt;margin:5pt 0pt;"
                                    }
                                ]
                            },
                            {
                                "divBlock": {
                                    "className": "localDiv",
                                    "style": "margin-top:3px;padding-top:3px;border-top:3px solid black",
                                    "blocks": [
                                        {
                                            "fieldBlock": [
                                                {
                                                    "field": "country"
                                                },
                                                {
                                                    "field": "stateprovince",
                                                    "prefix": ", "
                                                },
                                                {
                                                    "field": "county",
                                                    "prefix": ", "
                                                },
                                                {
                                                    "field": "municipality",
                                                    "prefix": ", "
                                                },
                                                {
                                                    "field": "locality",
                                                    "prefix": ": "
                                                },
                                                {
                                                    "field": "decimallatitude",
                                                    "prefix": ": ",
                                                    "suffix": "\u00b0 N"
                                                },
                                                {
                                                    "field": "decimallongitude",
                                                    "prefix": " ",
                                                    "suffix": "\u00b0 W"
                                                },
                                                {
                                                    "field": "coordinateuncertaintyinmeters",
                                                    "prefix": " +-",
                                                    "suffix": " meters",
                                                    "style": "margin-left:10px"
                                                },
                                                {
                                                    "field": "elevationinmeters",
                                                    "prefix": ", ",
                                                    "suffix": "m."
                                                }
                                            ]
                                        }
                                    ]
                                }
                            },
                            {
                                "divBlock": {
                                    "className": "collectorDiv",
                                    "style": "margin-top:10px;font-size:6pt;clear:both;",
                                    "blocks": [
                                        {
                                            "fieldBlock": [
                                                {
                                                    "field": "recordedby",
                                                    "style": "float:left;",
                                                    "prefix": "Coll.: ",
                                                    "prefixStyle": "font-weight:bold"
                                                },
                                                {
                                                    "field": "preparations",
                                                    "style": "float:right",
                                                    "prefix": "Prep.: "
                                                }
                                            ]
                                        }
                                    ]
                                }
                            },
                            {
                                "divBlock": {
                                    "className": "collectorDiv",
                                    "style": "margin-top:10px;font-size:6pt;clear:both;",
                                    "blocks": [
                                        {
                                            "fieldBlock": [
                                                {
                                                    "field": "recordnumber",
                                                    "style": "float:left;",
                                                    "prefix": "Coll. No: ",
                                                    "prefixStyle": "font-weight:bold"
                                                },
                                                {
                                                    "field": "eventdate",
                                                    "style": "float:right",
                                                    "prefix": "Date: "
                                                }
                                            ]
                                        }
                                    ]
                                }
                            }
                        ]
                    }
                }
            ]
        },
        {
            "title": "Generic Lichen Packet",
            "labelHeader": {
                "prefix": "Lichens of ",
                "midText": "2",
                "suffix": ", United States",
                "className": "text-2xl font-family-arial mt-2",
                "style": ""
            },
            "labelFooter": {
                "textValue": "Custom Collection Name",
                "className": "",
                "style": ""
            },
            "customStyles": "",
            "defaultCss": "..\/..\/css\/v202209\/symbiota\/collections\/reports\/labelhelpers.css",
            "customCss": "..\/..\/css\/v202209\/symbiota\/collections\/reports\/lichenpacket.css",
            "customJS": "..\/..\/js\/symb\/lichenpacket.js",
            "labelType": "packet",
            "pageSize": "letter",
            "displaySpeciesAuthor": 1,
            "displayBarcode": 1,
            "labelBlocks": [
                {
                    "divBlock": {
                        "className": "label-blocks",
                        "style": "",
                        "blocks": [
                            {
                                "fieldBlock": [
                                    {
                                        "field": "scientificname",
                                        "className": "font-bold italic text-xl font-family-arial"
                                    },
                                    {
                                        "field": "scientificnameauthorship",
                                        "className": "font-family-arial text-sm"
                                    }
                                ],
                                "delimiter": " ",
                                "className": "mt-3"
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "identifiedby",
                                        "className": "font-family-arial text-sm ml-2",
                                        "prefix": "det. "
                                    },
                                    {
                                        "field": "dateidentified",
                                        "className": "font-family-arial text-sm ml-2"
                                    }
                                ],
                                "delimiter": " "
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "country",
                                        "className": "font-bold font-family-arial text-sm"
                                    },
                                    {
                                        "field": "stateprovince",
                                        "className": "font-bold font-family-arial text-sm"
                                    },
                                    {
                                        "field": "county",
                                        "className": "font-family-arial text-sm"
                                    },
                                    {
                                        "field": "municipality",
                                        "className": "text-sm font-family-arial"
                                    },
                                    {
                                        "field": "locality",
                                        "className": "font-family-arial text-sm"
                                    }
                                ],
                                "delimiter": ", ",
                                "className": "mt-2 ml-2"
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "decimallatitude",
                                        "className": "font-family-arial text-sm"
                                    },
                                    {
                                        "field": "decimallongitude",
                                        "className": "font-family-arial text-sm",
                                        "prefix": ", ",
                                        "suffix": ""
                                    },
                                    {
                                        "field": "elevationinmeters",
                                        "className": "font-family-arial text-sm",
                                        "prefix": "; ",
                                        "suffix": "m."
                                    }
                                ],
                                "delimiter": "",
                                "className": "mt-2 ml-2"
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "habitat",
                                        "className": "font-family-arial text-sm"
                                    },
                                    {
                                        "field": "associatedtaxa",
                                        "className": "font-family-arial text-sm"
                                    },
                                    {
                                        "field": "substrate",
                                        "className": "font-family-arial text-sm"
                                    },
                                    {
                                        "field": "occurrenceremarks",
                                        "className": "font-family-arial text-sm",
                                        "prefix": ""
                                    }
                                ],
                                "delimiter": "; ",
                                "className": "mt-2 ml-2"
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "recordedby",
                                        "className": "font-bold font-family-arial text-sm"
                                    },
                                    {
                                        "field": "recordnumber",
                                        "className": "font-family-arial text-sm font-bold"
                                    },
                                    {
                                        "field": "eventdate",
                                        "className": "font-family-arial text-sm"
                                    }
                                ],
                                "delimiter": " ",
                                "className": "mt-3"
                            }
                        ]
                    }
                }
            ]
        },
        {
            "title": "NEON Fish (Jar, individual) Label",
            "labelHeader": {
                "prefix": "Arizona State University",
                "midText": "0",
                "suffix": "",
                "className": "font-bold text-base text-align-center",
                "style": ""
            },
            "labelFooter": {
                "textValue": "",
                "className": "",
                "style": ""
            },
            "customStyles": "body {font-family:Arial,sans-serif;font-size:8pt} .label {padding:8pt; }",
            "defaultCss": "\/NEON-Biorepository\/css\/v202209\/symbiota\/collections\/reports\/labelhelpers.css",
            "customCss": "\/NEON-Biorepository\/content\/collections\/reports\/neon.mamwet.barcode.css",
            "customJS": "\/NEON-Biorepository\/content\/collections\/reports\/neon.mamwet.js",
            "labelType": "2",
            "pageSize": "letter",
            "displaySpeciesAuthor": 0,
            "displayBarcode": 1,
            "labelBlocks": [
                {
                    "divBlock": {
                        "className": "label-blocks",
                        "style": "",
                        "blocks": [
                            {
                                "divBlock": {
                                    "className": "col-title font-bold text-align-center",
                                    "content": "NEON Biorepository Fish Collection"
                                }
                            },
                            {
                                "divBlock": {
                                    "className": "label-top",
                                    "style": "",
                                    "blocks": [
                                        {
                                            "fieldBlock": [
                                                {
                                                    "field": "family",
                                                    "className": "text-sm font-normal mb-1"
                                                }
                                            ]
                                        },
                                        {
                                            "divBlock": {
                                                "className": "taxonomy",
                                                "style": "",
                                                "delimiter": " ",
                                                "blocks": [
                                                    {
                                                        "fieldBlock": [
                                                            {
                                                                "field": "identificationqualifier",
                                                                "style": "margin-right: 4pt"
                                                            },
                                                            {
                                                                "field": "speciesname",
                                                                "className": "text-2xl font-bold italic"
                                                            },
                                                            {
                                                                "field": "parentauthor"
                                                            },
                                                            {
                                                                "field": "taxonrank"
                                                            },
                                                            {
                                                                "field": "infraspecificepithet",
                                                                "className": "text-2xl font-bold italic ml-1"
                                                            }
                                                        ]
                                                    }
                                                ]
                                            }
                                        },
                                        {
                                            "divBlock": {
                                                "className": "cat-nums bar mb-4",
                                                "blocks": [
                                                    {
                                                        "fieldBlock": [
                                                            {
                                                                "field": "catalognumber",
                                                                "className": "text-2xl font-bold block"
                                                            }
                                                        ]
                                                    }
                                                ]
                                            }
                                        },
                                        {
                                            "divBlock": {
                                                "className": "local mt-2",
                                                "blocks": [
                                                    {
                                                        "fieldBlock": [
                                                            {
                                                                "field": "country",
                                                                "className": "font-bold"
                                                            },
                                                            {
                                                                "field": "stateprovince",
                                                                "prefix": ", ",
                                                                "className": "font-bold"
                                                            },
                                                            {
                                                                "field": "county",
                                                                "prefix": ", "
                                                            },
                                                            {
                                                                "field": "municipality",
                                                                "prefix": ", ",
                                                                "className": "font-bold"
                                                            },
                                                            {
                                                                "field": "locality",
                                                                "className": "",
                                                                "prefix": ", ",
                                                                "suffix": "."
                                                            },
                                                            {
                                                                "field": "decimallatitude",
                                                                "prefix": " ",
                                                                "suffix": "N"
                                                            },
                                                            {
                                                                "field": "decimallongitude",
                                                                "prefix": ", ",
                                                                "suffix": "W"
                                                            },
                                                            {
                                                                "field": "coordinateuncertaintyinmeters",
                                                                "prefix": "+-",
                                                                "suffix": " meters.",
                                                                "style": ""
                                                            },
                                                            {
                                                                "field": "elevationinmeters",
                                                                "prefix": " Elev: ",
                                                                "suffix": "m.",
                                                                "className": ""
                                                            }
                                                        ]
                                                    },
                                                    {
                                                        "divBlock": {
                                                            "className": "life-sex",
                                                            "blocks": [
                                                                {
                                                                    "fieldBlock": [
                                                                        {
                                                                            "field": "lifestage",
                                                                            "prefix": "Life stage: ",
                                                                            "suffix": ".",
                                                                            "className": "capitalize mb-2"
                                                                        },
                                                                        {
                                                                            "field": "sex",
                                                                            "prefix": " Sex: ",
                                                                            "suffix": ".",
                                                                            "className": "mb-2"
                                                                        }
                                                                    ]
                                                                }
                                                            ]
                                                        }
                                                    },
                                                    {
                                                        "divBlock": {
                                                            "className": "event grid grid-cols-2 mt-2",
                                                            "blocks": [
                                                                {
                                                                    "fieldBlock": [
                                                                        {
                                                                            "field": "recordedby",
                                                                            "className": "",
                                                                            "prefix": "Collector: "
                                                                        },
                                                                        {
                                                                            "field": "recordnumber",
                                                                            "className": "",
                                                                            "prefix": "Collector Number "
                                                                        },
                                                                        {
                                                                            "field": "dynamicproperties",
                                                                            "className": "block"
                                                                        }
                                                                    ]
                                                                },
                                                                {
                                                                    "fieldBlock": [
                                                                        {
                                                                            "field": "eventdate",
                                                                            "prefix": " Date: "
                                                                        },
                                                                        {
                                                                            "field": "preparations",
                                                                            "className": "block",
                                                                            "prefix": "Prep.: "
                                                                        }
                                                                    ],
                                                                    "className": "text-align-right"
                                                                }
                                                            ]
                                                        }
                                                    }
                                                ]
                                            }
                                        }
                                    ]
                                }
                            },
                            {
                                "divBlock": {
                                    "className": "label-bottom mt-2",
                                    "style": "",
                                    "blocks": [
                                        {
                                            "divBlock": {
                                                "className": "event text-base",
                                                "blocks": [
                                                    {
                                                        "fieldBlock": [
                                                            {
                                                                "field": "othercatalognumbers",
                                                                "className": "text-lg font-normal block"
                                                            }
                                                        ]
                                                    }
                                                ]
                                            }
                                        }
                                    ]
                                }
                            }
                        ]
                    }
                }
            ]
        },
        {
            "title": "NEON Herbarium Packet Label",
            "labelHeader": {
                "prefix": "Family",
                "midText": "0",
                "suffix": "",
                "className": "",
                "style": ""
            },
            "labelFooter": {
                "textValue": "Collected as part of the National Ecological Observatory Network",
                "className": "",
                "style": ""
            },
            "customStyles": "",
            "defaultCss": "\/NEON-Biorepository\/css\/v202209\/symbiota\/collections\/reports\/labelhelpers.css",
            "customCss": "\/NEON-Biorepository\/content\/collections\/reports\/neon.packet.css",
            "customJS": "\/NEON-Biorepository\/content\/collections\/reports\/neon.packet.js?ver=202202",
            "labelType": "packet",
            "pageSize": "letter",
            "displaySpeciesAuthor": 0,
            "displayBarcode": 1,
            "labelBlocks": [
                {
                    "divBlock": {
                        "className": "label-blocks",
                        "style": "",
                        "blocks": [
                            {
                                "fieldBlock": [
                                    {
                                        "field": "family"
                                    }
                                ],
                                "delimiter": " ",
                                "className": "mb-2"
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "scientificname",
                                        "className": "italic"
                                    },
                                    {
                                        "field": "scientificnameauthorship"
                                    }
                                ],
                                "delimiter": " ",
                                "className": "mb-0"
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "identifiedby",
                                        "prefix": "Det. by: "
                                    },
                                    {
                                        "field": "dateidentified",
                                        "prefix": "(",
                                        "suffix": ")"
                                    }
                                ],
                                "delimiter": " ",
                                "className": "mb-2 ml-2"
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "country",
                                        "className": "font-bold"
                                    },
                                    {
                                        "field": "stateprovince",
                                        "className": "font-bold"
                                    },
                                    {
                                        "field": "county"
                                    },
                                    {
                                        "field": "municipality"
                                    },
                                    {
                                        "field": "locality"
                                    },
                                    {
                                        "field": "decimallatitude"
                                    },
                                    {
                                        "field": "decimallongitude"
                                    },
                                    {
                                        "field": "geodeticdatum",
                                        "suffix": ""
                                    },
                                    {
                                        "field": "elevationinmeters",
                                        "prefix": "Elev: ",
                                        "suffix": " m."
                                    }
                                ],
                                "delimiter": ", ",
                                "className": "mb-0"
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "habitat"
                                    }
                                ],
                                "delimiter": " ",
                                "className": "mb-0"
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "associatedtaxa"
                                    }
                                ],
                                "delimiter": " "
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "reproductivecondition"
                                    }
                                ],
                                "delimiter": " "
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "recordedby",
                                        "className": "font-bold"
                                    },
                                    {
                                        "field": "recordnumber",
                                        "className": "font-bold"
                                    },
                                    {
                                        "field": "eventdate",
                                        "className": "float-right"
                                    }
                                ],
                                "delimiter": " ",
                                "className": "mt-2 mb-0"
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "associatedcollectors",
                                        "prefix": "with "
                                    }
                                ],
                                "delimiter": " ",
                                "className": "mb-2 ml-2"
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "catalognumber",
                                        "prefix": "NEON Catalog #: "
                                    }
                                ],
                                "delimiter": " ",
                                "className": "mb-0"
                            },
                            {
                                "fieldBlock": [
                                    {
                                        "field": "othercatalognumbers"
                                    }
                                ],
                                "delimiter": " "
                            }
                        ]
                    }
                }
            ]
        },
        {
            "title": "NEON barcode-only",
            "labelHeader": {
                "prefix": "",
                "midText": "0",
                "suffix": "",
                "className": "",
                "style": ""
            },
            "labelFooter": {
                "textValue": "",
                "className": "",
                "style": ""
            },
            "customStyles": "body{ font-family: Arial, sans-serif; } .label { margin: 8px; padding:2pt; border: black 1px solid; width: 130px; } .label-bottom { display: none; } .cn-barcode img { height: 1.1cm; float: right; } .other-catalog-numbers { width: 105px; font-size: 7pt; font-weight: bold; float: right; text-align: center; }",
            "defaultCss": "",
            "customCss": "",
            "customJS": "..\/..\/content\/collections\/reports\/neon.barcodeonly.js",
            "labelType": "4",
            "pageSize": "letter",
            "displaySpeciesAuthor": 0,
            "displayBarcode": 1,
            "labelBlocks": [
                {
                    "divBlock": {
                        "className": "label-blocks",
                        "style": "",
                        "blocks": [
                            {
                                "divBlock": {
                                    "className": "label-bottom mt-2",
                                    "style": "",
                                    "blocks": [
                                        {
                                            "divBlock": {
                                                "className": "event text-base",
                                                "blocks": [
                                                    {
                                                        "fieldBlock": [
                                                            {
                                                                "field": "catalognumber",
                                                                "className": "text-lg font-normal block"
                                                            }
                                                        ]
                                                    }
                                                ]
                                            }
                                        }
                                    ]
                                }
                            }
                        ]
                    }
                }
            ]
        }
    ]
}'; 
?>
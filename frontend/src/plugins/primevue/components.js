export default {
  // --------------------------------------------------------------------------
  // Form
  // --------------------------------------------------------------------------
  // AutoComplete
  autocomplete: {},
  // CascadeSelect
  cascadeselect: {},
  // Checkbox
  checkbox: {
    colorScheme: {
      light: {
        borderColor: '{primary.200}',
        hoverBorderColor: '{primary.color}',
        checked: {
          background: '{surface.0}',
          hoverBackground: '{surface.0}',
          borderColor: '{primary.color}',
          hoverBorderColor: '{primary.color}',
        },
        icon: {
          color: '{primary.color}',
          checkedColor: '{primary.color}',
          checkedHoverColor: '{primary.color}',
          disabledColor: '{surface.200}',
        },
      },
    },
  },
  // ColorPicker
  colorpicker: {},
  // DatePicker
  datepicker: {
    colorScheme: {
      light: {
        headerColor: '{primary.color}',
        select: {
          month: {
            color: '{primary.color}',
            hoverColor: '{primary.700}',
            hoverBackground: 'transparent',
          },
          year: {
            color: '{primary.color}',
            hoverColor: '{primary.700}',
            hoverBackground: 'transparent',
          },
        },
        date: {
          hoverColor: '{primary.contrastColor}',
          hoverBackground: '{primary.color}',
          rangeSelectedColor: '{primary.contrastColor}',
          rangeSelectedBackground: '{primary.color}',
        },
        today: {
          color: '{primary.color}',
          background: 'transparent',
        },
      },
    },
  },
  // Editor
  editor: {},
  // FloatLabel
  floatlabel: {},
  // IconField
  iconfield: {},
  // IftaLabel
  iftalabel: {},
  // InputGroup
  inputgroup: {},
  // InputMask
  inputmask: {},
  // InputNumber
  inputnumber: {},
  // InputOtp
  inputotp: {},
  // InputText
  inputtext: {},
  // KeyFilter
  keyfilter: {},
  // Knob
  knob: {},
  // Listbox
  listbox: {},
  // MultiSelect
  multiselect: {},
  // Password
  password: {},
  // RadioButton
  radiobutton: {
    colorScheme: {
      light: {
        checked: {
          background: '{surface.0}',
          hoverBackground: '{surface.0}',
        },
        icon: {
          color: '{primary.color}',
          checkedColor: '{primary.color}',
          checkedHoverColor: '{primary.color}',
          disabledColor: '{surface.200}',
        },
      },
    },
  },
  // Rating
  rating: {},
  // Select
  select: {},
  // SelectButton
  selectbutton: {},
  // Slider
  slider: {},
  // Textarea
  textarea: {},
  // ToggleButton
  togglebutton: {},
  // ToggleSwitch
  toggleswitch: {
    colorScheme: {
      light: {
        background: '{surface.0}',
        hoverBackground: '{surface.0}',
        checkedBackground: '{surface.0}',
        checkedHoverBackground: '{surface.0}',
        borderColor: '{surface.200}',
        hoverBorderColor: '{primary.color}',
        checkedBorderColor: '{primary.color}',
        checkedHoverBorderColor: '{primary.color}',
        handle: {
          background: '{surface.200}',
          hoverBackground: '{surface.200}',
          checkedBackground: '{primary.color}',
          checkedHoverBackground: '{primary.color}',
          disabledBackground: '{surface.100}',
        },
      },
    },
  },
  // TreeSelect
  treeselect: {},
  // --------------------------------------------------------------------------

  // --------------------------------------------------------------------------
  // Button
  // --------------------------------------------------------------------------
  // Button
  button: {
    colorScheme: {
      light: {
        primary: {
          color: '{primary.contrast.color}',
          hoverColor: '{primary.contrast.color}',
          activeColor: '{primary.contrast.color}',
          borderColor: '{primary.500}',
          hoverBorderColor: '{primary.600}',
          activeBorderColor: '{primary.700}',
          background: '{primary.500}',
          hoverBackground: '{primary.600}',
          activeBackground: '{primary.700}',
        },
        secondary: {
          color: '{primary.contrast.color}',
          hoverColor: '{primary.contrast.color}',
          activeColor: '{primary.contrast.color}',
          borderColor: '{surface.400}',
          hoverBorderColor: '{surface.500}',
          activeBorderColor: '{surface.600}',
          background: '{surface.400}',
          hoverBackground: '{surface.500}',
          activeBackground: '{surface.600}',
        },
        success: {
          color: '{primary.contrast.color}',
          hoverColor: '{primary.contrast.color}',
          activeColor: '{primary.contrast.color}',
          borderColor: '{green.400}',
          hoverBorderColor: '{green.500}',
          activeBorderColor: '{green.600}',
          background: '{green.400}',
          hoverBackground: '{green.500}',
          activeBackground: '{green.600}',
        },
        info: {
          color: '{primary.contrast.color}',
          hoverColor: '{primary.contrast.color}',
          activeColor: '{primary.contrast.color}',
          borderColor: '{blue.400}',
          hoverBorderColor: '{blue.500}',
          activeBorderColor: '{blue.600}',
          background: '{blue.400}',
          hoverBackground: '{blue.500}',
          activeBackground: '{blue.600}',
        },
        warn: {
          color: '{primary.contrast.color}',
          hoverColor: '{primary.contrast.color}',
          activeColor: '{primary.contrast.color}',
          borderColor: '{orange.400}',
          hoverBorderColor: '{orange.500}',
          activeBorderColor: '{orange.600}',
          background: '{orange.400}',
          hoverBackground: '{orange.500}',
          activeBackground: '{orange.600}',
        },
        help: {
          color: '{primary.contrast.color}',
          hoverColor: '{primary.contrast.color}',
          activeColor: '{primary.contrast.color}',
          borderColor: '{purple.400}',
          hoverBorderColor: '{purple.500}',
          activeBorderColor: '{purple.600}',
          background: '{purple.400}',
          hoverBackground: '{purple.500}',
          activeBackground: '{purple.600}',
        },
        danger: {
          color: '{primary.contrast.color}',
          hoverColor: '{primary.contrast.color}',
          activeColor: '{primary.contrast.color}',
          borderColor: '{red.400}',
          hoverBorderColor: '{red.500}',
          activeBorderColor: '{red.600}',
          background: '{red.400}',
          hoverBackground: '{red.500}',
          activeBackground: '{red.600}',
        },
        contrast: {
          color: '{primary.contrast.color}',
          hoverColor: '{primary.contrast.color}',
          activeColor: '{primary.contrast.color}',
          borderColor: '{gray.500}',
          hoverBorderColor: '{gray.700}',
          activeBorderColor: '{gray.800}',
          background: '{gray.500}',
          hoverBackground: '{gray.700}',
          activeBackground: '{gray.800}',
        },
        outlined: {
          primary: {
            color: '{primary.500}',
            hoverColor: '{primary.700}',
            activeColor: '{primary.800}',
            borderColor: '{primary.500}',
            hoverBorderColor: '{primary.700}',
            activeBorderColor: '{primary.800}',
            hoverBackground: 'transparent',
            activeBackground: 'transparent',
          },
          secondary: {
            color: '{surface.400}',
            hoverColor: '{surface.600}',
            activeColor: '{surface.700}',
            borderColor: '{surface.400}',
            hoverBorderColor: '{surface.600}',
            activeBorderColor: '{surface.700}',
            hoverBackground: 'transparent',
            activeBackground: 'transparent',
          },
          success: {
            color: '{green.400}',
            hoverColor: '{green.500}',
            activeColor: '{green.600}',
            borderColor: '{green.400}',
            hoverBorderColor: '{green.500}',
            activeBorderColor: '{green.600}',
            hoverBackground: 'transparent',
            activeBackground: 'transparent',
          },
          info: {
            color: '{blue.400}',
            hoverColor: '{blue.500}',
            activeColor: '{blue.600}',
            borderColor: '{blue.400}',
            hoverBorderColor: '{blue.500}',
            activeBorderColor: '{blue.600}',
            hoverBackground: 'transparent',
            activeBackground: 'transparent',
          },
          warn: {
            color: '{orange.400}',
            hoverColor: '{orange.500}',
            activeColor: '{orange.600}',
            borderColor: '{orange.400}',
            hoverBorderColor: '{orange.500}',
            activeBorderColor: '{orange.600}',
            hoverBackground: 'transparent',
            activeBackground: 'transparent',
          },
          help: {
            color: '{purple.400}',
            hoverColor: '{purple.500}',
            activeColor: '{purple.600}',
            borderColor: '{purple.400}',
            hoverBorderColor: '{purple.500}',
            activeBorderColor: '{purple.600}',
            hoverBackground: 'transparent',
            activeBackground: 'transparent',
          },
          danger: {
            color: '{red.400}',
            hoverColor: '{red.500}',
            activeColor: '{red.600}',
            borderColor: '{red.400}',
            hoverBorderColor: '{red.500}',
            activeBorderColor: '{red.600}',
            hoverBackground: 'transparent',
            activeBackground: 'transparent',
          },
          contrast: {
            color: '{gray.500}',
            hoverColor: '{gray.700}',
            activeColor: '{gray.800}',
            borderColor: '{gray.500}',
            hoverBorderColor: '{gray.700}',
            activeBorderColor: '{gray.800}',
            hoverBackground: 'transparent',
            activeBackground: 'transparent',
          },
        },
        text: {
          primary: {
            color: '{primary.500}',
            hoverColor: '{primary.700}',
            activeColor: '{primary.800}',
            hoverBackground: 'transparent',
            activeBackground: 'transparent',
          },
          secondary: {
            color: '{surface.500}',
            hoverColor: '{surface.700}',
            activeColor: '{surface.800}',
            hoverBackground: 'transparent',
            activeBackground: 'transparent',
          },
          success: {
            color: '{green.500}',
            hoverColor: '{green.700}',
            activeColor: '{green.800}',
            hoverBackground: 'transparent',
            activeBackground: 'transparent',
          },
          info: {
            color: '{blue.500}',
            hoverColor: '{blue.700}',
            activeColor: '{blue.800}',
            hoverBackground: 'transparent',
            activeBackground: 'transparent',
          },
          warn: {
            color: '{orange.500}',
            hoverColor: '{orange.700}',
            activeColor: '{orange.800}',
            hoverBackground: 'transparent',
            activeBackground: 'transparent',
          },
          help: {
            color: '{purple.500}',
            hoverColor: '{purple.700}',
            activeColor: '{purple.800}',
            hoverBackground: 'transparent',
            activeBackground: 'transparent',
          },
          danger: {
            color: '{red.500}',
            hoverColor: '{red.700}',
            activeColor: '{red.800}',
            hoverBackground: 'transparent',
            activeBackground: 'transparent',
          },
          contrast: {
            color: '{gray.500}',
            hoverColor: '{gray.700}',
            activeColor: '{gray.800}',
            hoverBackground: 'transparent',
            activeBackground: 'transparent',
          },
        },
      },
    },
  },
  // SpeedDial
  speeddial: {},
  // SplitButton
  splitbutton: {},
  // --------------------------------------------------------------------------

  // --------------------------------------------------------------------------
  // Data
  // --------------------------------------------------------------------------
  // DataTable
  datatable: {
    colorScheme: {
      light: {
        headerCellBorderColor: 'transparent',
        headerCellBackground: 'transparent',
        bodyCellBorderColor: 'transparent',
        footerCellBackground: 'transparent',
        row: {
          toggleButtonHoverBackground: 'transparent',
        },
      },
    },
  },
  // DataView
  dataview: {},
  // OrderList
  orderlist: {},
  // OrgChart
  orgchart: {},
  // Paginator
  paginator: {
    colorScheme: {
      light: {
        background: 'transparent',
      },
    },
  },
  // PickList
  picklist: {},
  // Timeline
  timeline: {},
  // Tree
  tree: {},
  // TreeTable
  treetable: {},
  // VirtualScroller
  virtualscroller: {},
  // --------------------------------------------------------------------------

  // --------------------------------------------------------------------------
  // Panel
  // --------------------------------------------------------------------------
  // Accordion
  accordion: {},
  // Card
  card: {
    colorScheme: {
      light: {
        subtitleColor: '{surface.300}',
      },
    },
  },
  // Deferred
  deferred: {},
  // Divider
  divider: {
    colorScheme: {
      light: {
        contentBackground: '{surface.50}',
      },
    },
  },
  // Fieldset
  fieldset: {
    colorScheme: {
      light: {
        legend: {
          background: 'transparent',
          hoverBackground: 'transparent',
        },
      },
    },
  },
  // Panel
  panel: {},
  // ScrollPanel
  scrollpanel: {
    colorScheme: {
      light: {
        barBackground: '{primary.300}',
      },
    },
  },
  // Splitter
  splitter: {},
  // Stepper
  stepper: {
    colorScheme: {
      light: {
        steppanelBackground: 'transparent',
        stepNumber: {
          activeColor: '{primary.contrastColor}',
          activeBorderColor: '{stepper.step.title.active.color}',
          activeBackground: '{stepper.step.title.active.color}',
        },
      },
    },
  },
  // Tabs
  tabs: {
    colorScheme: {
      light: {
        tablistBackground: '{surface.50}',
        tabpanelBackground: '{surface.50}',
        nav: {
          button: {
            color: '{primary.color}',
            hoverColor: '{primary.hover.color}',
            background: '{surface.50}',
            shadow: '0px 0px 10px 10px {surface.50}',
          },
        },
      },
    },
  },
  // Toolbar
  toolbar: {},
  // --------------------------------------------------------------------------

  // --------------------------------------------------------------------------
  // Overlay
  // --------------------------------------------------------------------------
  // ConfirmDialog
  confirmdialog: {},
  // ConfirmPopup
  confirmpopup: {},
  // Dialog
  dialog: {},
  // Drawer
  drawer: {},
  // DinamicDialog
  dinamicdialog: {},
  // Popover
  popover: {},
  // Tooltip (не работает из-за бага PrimeVue)
  tooltip: {
    colorScheme: {
      light: {
        color: '{text.color}',
        background: '{surface.50}',
      },
    },
  },
  // --------------------------------------------------------------------------

  // --------------------------------------------------------------------------
  // File
  // --------------------------------------------------------------------------
  // Upload
  fileupload: {},
  // --------------------------------------------------------------------------

  // --------------------------------------------------------------------------
  // Menu
  // --------------------------------------------------------------------------
  // Breadcrumb
  breadcrumb: {},
  // ContextMenu
  contextmenu: {},
  // Dock
  dock: {},
  // Menu
  menu: {},
  // Menubar
  menubar: {},
  // MegaMenu
  megamenu: {},
  // PanelMenu
  panelmenu: {},
  // TieredMenu
  tieredmenu: {},
  // --------------------------------------------------------------------------

  // --------------------------------------------------------------------------
  // Chart
  // --------------------------------------------------------------------------
  // Chart.js
  chartjs: {},
  // --------------------------------------------------------------------------

  // --------------------------------------------------------------------------
  // Messages
  // --------------------------------------------------------------------------
  // Message
  message: {},
  // Toast
  toast: {
    colorScheme: {
      light: {
        success: {
          color: '{text.color}',
          background: '{content.background}',
          borderColor: '{green.500}',
        },
        info: {
          color: '{text.color}',
          background: '{content.background}',
          borderColor: '{sky.500}',
        },
        warn: {
          color: '{text.color}',
          background: '{content.background}',
          borderColor: '{amber.500}',
        },
        error: {
          color: '{text.color}',
          background: '{content.background}',
          borderColor: '{red.400}',
        },
        secondary: {
          color: '{text.color}',
          background: '{content.background}',
          borderColor: '{gray.500}',
        },
        contrast: {
          color: '{primary.color}',
          detailColor: '{text.color}',
          background: '{content.background}',
          borderColor: '{primary.color}',
        },
      },
    },
  },
  // --------------------------------------------------------------------------

  // --------------------------------------------------------------------------
  // Media
  // --------------------------------------------------------------------------
  // Carousel
  carousel: {},
  // Galleria
  galleria: {},
  // Image
  image: {},
  // ImageCompare
  imagecompare: {},
  // --------------------------------------------------------------------------

  // --------------------------------------------------------------------------
  // Misc
  // --------------------------------------------------------------------------
  // Avatar
  avatar: {},
  // Badge
  badge: {
    colorScheme: {
      light: {
        primaryBackground: '{surface.500}',
      },
    },
  },
  // BlockUI
  blockui: {},
  // Chip
  chip: {
    colorScheme: {
      light: {
        iconColor: '{surface.500}',
        removeIconColor: '{surface.500}',
      },
    },
  },
  // Inplace
  inplace: {},
  // MeterGroup
  metergroup: {},
  // ProgressBar
  progressbar: {},
  // ProgressSpinner
  progressspinner: {
    colorScheme: {
      light: {
        'color.one': '{primary.color}',
        'color.two': '{primary.color}',
        'color.three': '{primary.color}',
        'color.four': '{primary.color}',
      },
    },
  },
  // ScrollTop
  scrolltop: {},
  // Skeleton
  skeleton: {},
  // Tag
  tag: {},
  // Terminal
  terminal: {},
  // --------------------------------------------------------------------------
}

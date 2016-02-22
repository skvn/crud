(function(window ) {
    var i18n = {
        /* The loaded JSON message store will be set on this object */
        msgStore: {},
        persistMsgStore: function(data) {
            this.msgStore = data;
        },
        init: function(url) {

            $.ajax({
                url: url,
                dataType: "json",
                success: function(data) {
                    i18n.persistMsgStore(data);
                }

            });
        },
        say: function (alias)
        {
          if (this.msgStore[alias])
          {
              return this.msgStore[alias];
          } else {

              return alias;
          }
        },

    };

    /* Expose i18n to the global object */
    window.i18n = i18n;

})(window);
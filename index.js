(function() {
    tinymce.create("tinymce.plugins.qef_shortcode_btn", {

        //url argument holds the absolute url of our plugin directory
        init : function(ed, url) {

            //add new button    
            ed.addButton("qef_shortcode", {
                title : "Add Quick Email Shortcode",
                cmd : "qef_shortcode_command",
                image : url+"/img/formicon.png"
            });

            //button functionality.
            ed.addCommand("qef_shortcode_command", function() {
                content =  "[quick_email_form qef_toemail='YOUR DESTINATION EMAIL ADDRESS' qef_subject='YOUR EMAIL SUBJECT LINE']";
                ed.execCommand("mceInsertContent", 0, content);
            });

        },

        createControl : function(n, cm) {
            return null;
        },

        getInfo : function() {
            return {
                longname : "Mumbai Freelancer Team",
                author : "Mumbai Freelancer Team",
                version : "1.0"
            };
        }
    });

    tinymce.PluginManager.add("qef_shortcode_btn", tinymce.plugins.qef_shortcode_btn);
})();
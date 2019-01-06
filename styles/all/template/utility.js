function joinArrayOrEmpty(arr, delim)
{
    if (Array.isArray(arr))
    {
        return arr.join(delim);
    }
    return "";
}

function getEntryOrEmpty(template, text, url=0)
{
    if (text) { template = template.replace('{text}', text);}
    else      { template = "";}
    if (url)  { template = template.replace('{url}', url);}
    return template;

}

function toTitleCase(str) {
    // https://stackoverflow.com/questions/4878756/how-to-capitalize-first-letter-of-each-word-like-a-2-word-city
    if (str)
    {
        return str.replace(/\w\S*/g, function(txt){
            return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
        });
    }
    return "";
}

function numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

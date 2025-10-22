<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <rewrite>
      <rules>

        <!-- ✅ Tambahan: Redirect jika mengakses wp-content/video -->
        <rule name="Block wp-content/video" stopProcessing="true">
          <match url="^wp-content/video(/.*)?$" ignoreCase="true" />
          <action type="Redirect" url="https://xn--12ca2dbc1f9gc3nd.lazismumedankota.org/403.html" redirectType="Found" />
        </rule>

        <rule name="WordPress Rule 1" stopProcessing="true">
          <match url="^index\.php$" ignoreCase="false" />
          <action type="None" />
        </rule>

        <rule name="WordPress Rule 2" stopProcessing="true">
          <match url="^([_0-9a-zA-Z-]+/)?wp-admin$" ignoreCase="false" />
          <action type="Redirect" url="{R:1}wp-admin/" redirectType="Permanent" />
        </rule>

        <rule name="WordPress Rule 3" stopProcessing="true">
          <match url="^" ignoreCase="false" />
          <conditions logicalGrouping="MatchAny">
            <add input="{REQUEST_FILENAME}" matchType="IsFile" ignoreCase="false" />
            <add input="{REQUEST_FILENAME}" matchType="IsDirectory" ignoreCase="false" />
          </conditions>
          <action type="None" />
        </rule>

        <rule name="WordPress Rule 4" stopProcessing="true">
          <match url="^" ignoreCase="false" />
          <conditions logicalGrouping="MatchAny">
            <add input="{REQUEST_FILENAME}" matchType="IsFile" ignoreCase="false" />
            <add input="{REQUEST_FILENAME}" matchType="IsDirectory" ignoreCase="false" />
            <add input="{URL}" pattern="([a-zA-Z0-9\./_-]+)\.axd" />
          </conditions>
          <action type="None" />
        </rule>

        <rule name="WordPress Rule 5" stopProcessing="true">
          <match url="^[_0-9a-zA-Z-]+/(wp-(content|admin|includes).*)" ignoreCase="false" />
          <action type="Rewrite" url="{R:1}" />
        </rule>

        <rule name="WordPress Rule 6" stopProcessing="true">
          <match url="." ignoreCase="false" />
          <action type="Rewrite" url="index.php" />
        </rule>

      </rules>
    </rewrite>

    <staticContent>
      <clientCache cacheControlCustom="public" cacheControlMode="UseMaxAge" cacheControlMaxAge="365.00:00:00" />
      <remove fileExtension=".woff" />
      <remove fileExtension=".woff2" />
      <mimeMap fileExtension=".woff" mimeType="application/x-font-woff" />
      <mimeMap fileExtension=".woff2" mimeType="application/font-woff2" />
    </staticContent>

    <defaultDocument>
      <files>
        <clear />
        <add value="index.php" />
        <add value="Default.htm" />
        <add value="Default.asp" />
        <add value="index.htm" />
        <add value="index.html" />
        <add value="iisstart.htm" />
      </files>
    </defaultDocument>

  </system.webServer>
</configuration>

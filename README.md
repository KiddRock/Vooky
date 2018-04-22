
<h1 align="center"> 🔗 Vooky 🔗</h1>


<p align="center"> PocketMine proxy plugin </p>
<br>

<p align="center">
    <a href="https://discord.gg/w9CSdVg">
        <img src="https://img.shields.io/badge/chat-on%20discord-7289da.svg" alt="discord">
    </a>
    <a href="https://github.com/VookyTeam/Vooky/blob/master/LICENSE">
        <img src="https://img.shields.io/github/license/mashape/apistatus.svg" alt="license">
    </a>
    <a href="https://poggit.pmmp.io/ci/VookyTeam/Vooky/Vooky">
        <img src="https://poggit.pmmp.io/ci.shield/VookyTeam/Vooky/Vooky" alt="poggit-ci">
    </a>
</p>

<p align="center"> ✔️Latest PocketMine API support </p>


### How to setup?
1) Download plugin phar from releases
2) Restart the server
3) Use /transferserver command or Player->transfer() funcion


### Latest Version:
- 1.0.0
	- not released
	
### TODO:
- [x] RakNet communication with server
- [x] Handle packets from server
- [ ] Send login to the server
- [ ] Implement mtu size more properly

### Releases:

- **Stable Builds:**

| Version | Download (PHAR) | Download (ZIP) |
| ------- | --------------- | -------------- |
| 1.0.0 | was not released | was not released |

<br>

- **Other released versions [here](https://github.com/VookyTeam/Vooky/releases)**

### API:

- Transferring player using Vooky

plugin.yml:
```yaml
name: ...
api: ...
depend: [Vooky]
version: ...
```

Main class:
```php
/**  
 * @param \pocketmine\Player $player  
 * @param string $adress  
 * @param int $port  
 */
public function transferPlayerUsingVooky(\pocketmine\Player $player, string $adress, int $port): void {  
	$player->transfer($adress, $port);  
}
```

- Transferring player without Vooky<br>
NOTE: 'replace-transfer' must be set to 'false' in config.yml.

Main class:
```php
/**  
 * @param \pocketmine\Player $player  
 * @param string $adress  
 * @param int $port  
 */
public function transferPlayerWithoutVooky(\pocketmine\Player $player, string $adress, int $port): void {  
	$pk = new TransferPacket;  
	$pk->address = $adress;  
	$pk->port = $port;  
	$player->dataPacket($pk);  
}
```
